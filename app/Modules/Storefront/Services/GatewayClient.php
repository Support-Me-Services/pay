<?php

namespace App\Modules\Storefront\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Klient REST API bramki płatności, auth: X-Api-Key.
 *
 * Adres bazowy = host BIEŻĄCEGO żądania (np. https://please-support-me.com),
 * bo trasy płatności bramki działają teraz także na domenie sklepu. Dzięki temu
 * zwracany payment_url (route('pay.show')) jest na tej samej domenie i klient
 * nie jest przekierowywany na subdomenę pay.*. W trybie CLI (kolejki, harmonogram)
 * brak żądania HTTP — wtedy fallback na config('shop.gateway_url').
 */
class GatewayClient
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $request = request();
        $this->baseUrl = ($request !== null && $request->getHost() !== '')
            ? $request->getSchemeAndHttpHost()
            : config('shop.gateway_url');
        $this->apiKey = config('shop.gateway_api_key');
    }

    /**
     * Tworzy transakcję w bramce; zwraca ['uuid' => ..., 'payment_url' => ...].
     */
    public function createTransaction(array $payload): array
    {
        $response = $this->request()->post($this->baseUrl . '/api/v1/transactions', $payload);

        if ($response->status() !== 201 || ! $response->json('payment_url')) {
            Log::error('Bramka: utworzenie transakcji nieudane', [
                'http' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new RuntimeException('Bramka płatności niedostępna (HTTP ' . $response->status() . ')');
        }

        return $response->json();
    }

    /**
     * Pobiera status transakcji — sklep nie ufa samemu redirectowi.
     */
    public function getTransaction(string $uuid): ?array
    {
        $response = $this->request()->get($this->baseUrl . '/api/v1/transactions/' . $uuid);

        if (! $response->ok()) {
            Log::warning('Bramka: odczyt transakcji nieudany', ['uuid' => $uuid, 'http' => $response->status()]);

            return null;
        }

        return $response->json();
    }

    /**
     * Raportuje event (tag_open) do bramki. Nie blokuje flow przy błędzie.
     */
    public function sendEvent(string $type, ?string $tagUid = null): void
    {
        try {
            $this->request()->timeout(3)->post($this->baseUrl . '/api/v1/events', [
                'type' => $type,
                'tag_uid' => $tagUid,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Bramka: wysyłka eventu nieudana', ['type' => $type, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Weryfikacja podpisu HMAC-SHA256 webhooka przychodzącego z bramki.
     */
    public function verifyWebhookSignature(string $body, string $signature): bool
    {
        if ($this->apiKey === '' || $signature === '') {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $body, $this->apiKey), strtolower($signature));
    }

    private function request()
    {
        return Http::withHeaders(['X-Api-Key' => $this->apiKey])
            ->acceptJson()
            ->timeout(10);
    }
}
