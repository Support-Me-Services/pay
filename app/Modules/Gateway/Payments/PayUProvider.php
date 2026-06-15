<?php

namespace App\Modules\Gateway\Payments;

use App\Modules\Gateway\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * PayU REST API v2.1 (https://developers.payu.com/europe/pl/docs/)
 * OAuth client_credentials + POST /api/v2_1/orders + webhook OpenPayu-Signature.
 */
class PayUProvider implements PaymentProviderInterface
{
    private const TOKEN_CACHE_KEY = 'payu_access_token';

    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('payment.payu.base_url'), '/');
    }

    public function createTransaction(Transaction $transaction, string $customerIp, array $context = []): TransactionDto
    {
        $payload = [
            'merchantPosId' => (string) config('payment.payu.pos_id'),
            'extOrderId' => $transaction->id,
            'customerIp' => $customerIp ?: '127.0.0.1',
            'description' => $transaction->product_name,
            'currencyCode' => $transaction->currency,
            'totalAmount' => (string) $transaction->amount, // grosze
            'continueUrl' => route('pay.return', $transaction->id),
            'notifyUrl' => route('webhooks.payu'),
            'products' => [[
                'name' => $transaction->product_name,
                'unitPrice' => (string) $transaction->amount,
                'quantity' => '1',
            ]],
        ];

        $blikCode = preg_replace('/\D/', '', (string) ($context['blik_code'] ?? ''));
        $pbl = (string) ($context['pbl'] ?? '');

        if ($pbl !== '') {
            // App2app przez pay-by-link konkretnego banku: redirect z PayU
            // otwiera na telefonie aplikację banku (universal/app link),
            // klient potwierdza biometrią — bez kodu BLIK.
            $payload['payMethods'] = [
                'payMethod' => ['type' => 'PBL', 'value' => $pbl],
            ];
        } elseif ($blikCode !== '') {
            // BLIK Level 0: kod wpisany na hostowanej stronie bramki — zamówienie
            // autoryzuje się w tle, klient potwierdza push w aplikacji banku.
            // BLIK wymaga obiektu buyer z e-mailem; nie zbieramy danych klienta,
            // więc używamy syntetycznego adresu per transakcja.
            $payload['buyer'] = [
                'email' => 'klient+' . substr($transaction->id, 0, 8) . '@pay.redai.pl',
                'language' => 'pl',
            ];
            $payload['payMethods'] = [
                'payMethod' => ['type' => 'BLIK_AUTHORIZATION_CODE', 'value' => $blikCode],
            ];
        } elseif ($transaction->mode === 'app2app') {
            // Fallback bez kodu: wymuszony BLIK (PBL) — strona BLIK z polem na kod.
            $payload['payMethods'] = [
                'payMethod' => ['type' => 'PBL', 'value' => 'blik'],
            ];
        }

        $response = $this->request()->post($this->baseUrl . '/api/v2_1/orders', $payload);

        // PayU zwraca 302 z JSON-em w body — nie podążamy za redirectem.
        $data = $response->json();
        $statusCode = $data['status']['statusCode'] ?? null;

        // BLIK Level 0: SUCCESS bez redirectUri — płatność czeka na potwierdzenie
        // w aplikacji banku, status przyjdzie webhookiem.
        if ($blikCode !== '' && $statusCode === 'SUCCESS') {
            return new TransactionDto(
                providerOrderId: $data['orderId'] ?? '',
                redirectUrl: $data['redirectUri'] ?? '',
            );
        }

        if (! in_array($statusCode, ['SUCCESS', 'WARNING_CONTINUE_3DS', 'WARNING_CONTINUE_REDIRECT'])
            || empty($data['redirectUri'])) {
            Log::error('PayU: utworzenie zamówienia nieudane', [
                'transaction' => $transaction->id,
                'http' => $response->status(),
                'body' => $data,
            ]);
            throw new RuntimeException('PayU: nie udało się utworzyć płatności (' . ($statusCode ?? 'HTTP ' . $response->status()) . ')');
        }

        return new TransactionDto(
            providerOrderId: $data['orderId'] ?? '',
            redirectUrl: $data['redirectUri'],
        );
    }

    /**
     * Bieżący status zamówienia w PayU (aktywna rekonsyliacja — nie polegamy
     * wyłącznie na webhooku).
     */
    public function getOrderStatus(string $orderId): ?string
    {
        $response = $this->request()->get($this->baseUrl . '/api/v2_1/orders/' . $orderId);

        return $response->ok() ? $response->json('orders.0.status') : null;
    }

    /**
     * Odbiór (capture) płatności czekającej na potwierdzenie sprzedawcy.
     */
    public function capture(string $orderId): bool
    {
        $response = $this->request()->put(
            $this->baseUrl . '/api/v2_1/orders/' . $orderId . '/status',
            ['orderStatus' => 'COMPLETED']
        );

        return $response->ok() && $response->json('status.statusCode') === 'SUCCESS';
    }

    /**
     * Lista banków pay-by-link dostępnych na POS-ie (do ekranu wyboru banku).
     * Zwraca [['value' => 'ab', 'name' => 'Płacę z Alior Bankiem', 'image' => url], ...]
     */
    public function payByLinks(): array
    {
        return Cache::remember('payu_paybylinks', 3600, function () {
            $response = $this->request()->get($this->baseUrl . '/api/v2_1/paymethods', ['lang' => 'pl']);

            $banks = [];
            foreach ($response->json('payByLinks', []) as $method) {
                if (($method['status'] ?? '') !== 'ENABLED') {
                    continue;
                }
                // blik ma własną ścieżkę; 'b' = przelew tradycyjny (wolny) — pomijamy
                if (in_array($method['value'], ['blik', 'b', 'dpp', 'dpt', 'c'])) {
                    continue;
                }
                $banks[] = [
                    'value' => $method['value'],
                    'name' => $method['name'],
                    'image' => $method['brandImageUrl'] ?? null,
                ];
            }

            return $banks;
        });
    }

    public function getRedirectUrl(Transaction $transaction): string
    {
        if (! $transaction->provider_redirect_url) {
            throw new RuntimeException('Brak URL przekierowania — najpierw utwórz transakcję u operatora.');
        }

        return $transaction->provider_redirect_url;
    }

    public function handleWebhook(Request $request): WebhookResult
    {
        $body = $request->getContent();

        if (! $this->verifySignature($body, $request->header('OpenPayu-Signature', ''))) {
            Log::warning('PayU webhook: niepoprawny podpis', ['ip' => $request->ip()]);

            return new WebhookResult(valid: false);
        }

        $order = $request->json('order', []);
        $extOrderId = $order['extOrderId'] ?? null;
        $payuStatus = $order['status'] ?? null;

        $status = match ($payuStatus) {
            'COMPLETED' => WebhookResult::STATUS_PAID,
            'CANCELED' => WebhookResult::STATUS_FAILED,
            default => WebhookResult::STATUS_IGNORED, // PENDING, WAITING_FOR_CONFIRMATION...
        };

        return new WebhookResult(valid: true, transactionId: $extOrderId, status: $status);
    }

    /**
     * OpenPayu-Signature: "sender=...;signature=...;algorithm=MD5;content=DOCUMENT"
     * Podpis = hash(body + second_key) wskazanym algorytmem.
     */
    private function verifySignature(string $body, string $header): bool
    {
        $secondKey = (string) config('payment.payu.second_key');
        if ($secondKey === '' || $header === '') {
            return false;
        }

        $parts = [];
        foreach (explode(';', $header) as $pair) {
            $kv = explode('=', trim($pair), 2);
            if (count($kv) === 2) {
                $parts[strtolower($kv[0])] = $kv[1];
            }
        }

        $signature = strtolower($parts['signature'] ?? '');
        $algorithm = strtoupper($parts['algorithm'] ?? 'MD5');

        $expected = match ($algorithm) {
            'MD5' => md5($body . $secondKey),
            'SHA-256', 'SHA256' => hash('sha256', $body . $secondKey),
            'SHA-1', 'SHA1' => sha1($body . $secondKey),
            default => null,
        };

        return $expected !== null && hash_equals($expected, $signature);
    }

    private function request()
    {
        return Http::withToken($this->accessToken())
            ->acceptJson()
            ->asJson()
            ->withOptions(['allow_redirects' => false])
            ->timeout(15);
    }

    private function accessToken(): string
    {
        $token = Cache::get(self::TOKEN_CACHE_KEY);
        if ($token) {
            return $token;
        }

        return $this->fetchAccessToken();
    }

    private function fetchAccessToken(): string
    {
        $response = Http::asForm()->timeout(15)->post(
            $this->baseUrl . '/pl/standard/user/oauth/authorize',
            [
                'grant_type' => 'client_credentials',
                'client_id' => config('payment.payu.client_id') ?: config('payment.payu.pos_id'),
                'client_secret' => config('payment.payu.client_secret'),
            ]
        );

        if (! $response->ok() || ! $response->json('access_token')) {
            Log::error('PayU OAuth: błąd autoryzacji', ['http' => $response->status(), 'body' => $response->body()]);
            throw new RuntimeException('PayU: autoryzacja OAuth nieudana (HTTP ' . $response->status() . ')');
        }

        $token = $response->json('access_token');
        // Token ważny ~12 h (expires_in w sekundach) — cache z marginesem 5 min.
        $ttl = max(60, (int) $response->json('expires_in', 43199) - 300);
        Cache::put(self::TOKEN_CACHE_KEY, $token, $ttl);

        return $token;
    }
}
