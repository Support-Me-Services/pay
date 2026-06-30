<?php

namespace App\Modules\Gateway\Http\Controllers;

use App\Modules\Gateway\Models\Transaction;
use App\Modules\Gateway\Payments\PaymentProviderInterface;
use App\Modules\Gateway\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentProviderInterface $provider,
        private readonly TransactionService $transactions,
    ) {
    }

    /**
     * GET /pay/{uuid} — wejście klienta z przekierowania sklepu.
     *
     * classic: tworzy płatność u operatora i robi 302 na jego stronę.
     * app2app + PayU: hostowana strona BLIK Level 0 (kod wpisywany u nas,
     * potwierdzenie pushem w aplikacji banku klienta).
     * app2app + Mock: ekran przejściowy i symulator aplikacji banku.
     */
    public function show(Request $request, string $uuid)
    {
        $transaction = Transaction::findOrFail($uuid);

        if ($transaction->status === 'paid') {
            return redirect()->away($transaction->return_url);
        }

        // Hostowany app2app: wybór banku (PBL → aplikacja banku + biometria)
        // lub kod BLIK. Zamówienie u operatora powstaje po wyborze metody.
        if ($transaction->mode === 'app2app' && config('payment.provider') === 'payu') {
            $banks = [];
            if ($this->provider instanceof \App\Modules\Gateway\Payments\PayUProvider) {
                try {
                    $banks = $this->provider->payByLinks();
                } catch (\Throwable $e) {
                    Log::warning('PayU: pobranie metod PBL nieudane', ['error' => $e->getMessage()]);
                }
            }

            return view('payment.app2app', [
                'transaction' => $transaction,
                'banks' => $banks,
                // klient wrócił na stronę, mając już zamówienie u operatora —
                // pozwól dokończyć w aplikacji banku
                'continueUrl' => $transaction->status === 'pending' ? $transaction->provider_redirect_url : null,
            ]);
        }

        if ($transaction->status === 'created') {
            try {
                $dto = $this->provider->createTransaction($transaction, $request->ip());
            } catch (\Throwable $e) {
                Log::error('Utworzenie płatności u operatora nieudane', [
                    'transaction' => $transaction->id,
                    'error' => $e->getMessage(),
                ]);

                return response()->view('payment.error', ['transaction' => $transaction], 502);
            }

            $transaction->update([
                'status' => 'pending',
                'provider_order_id' => $dto->providerOrderId,
                'provider_redirect_url' => $dto->redirectUrl,
            ]);

            $this->transactions->logEvent($transaction, 'payment_started');
        }

        $redirectUrl = $this->provider->getRedirectUrl($transaction);

        if ($transaction->mode === 'app2app') {
            return view('payment.transition', [
                'transaction' => $transaction,
                'redirectUrl' => $redirectUrl,
            ]);
        }

        return redirect()->away($redirectUrl);
    }

    /**
     * POST /pay/{uuid}/blik — BLIK Level 0: tworzy zamówienie PayU z kodem
     * wpisanym na stronie bramki. Klient potwierdza push w aplikacji banku,
     * a status przychodzi webhookiem (frontend polluje /pay/{uuid}/status).
     */
    public function blik(Request $request, string $uuid)
    {
        $transaction = Transaction::findOrFail($uuid);

        if ($transaction->isFinal()) {
            return response()->json(['status' => $transaction->status, 'redirect' => route('pay.return', $transaction->id)]);
        }

        $data = $request->validate([
            'code' => ['required', 'regex:/^\d{6}$/'],
        ], ['code.regex' => 'Kod BLIK to 6 cyfr.', 'code.required' => 'Wpisz kod BLIK.']);

        try {
            $dto = $this->provider->createTransaction($transaction, $request->ip(), [
                'blik_code' => $data['code'],
            ]);
        } catch (\Throwable $e) {
            Log::warning('BLIK Level 0: autoryzacja nieudana', [
                'transaction' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Płatność nie została zautoryzowana. Sprawdź kod i spróbuj ponownie.',
            ], 422);
        }

        if ($transaction->status === 'created') {
            $this->transactions->logEvent($transaction, 'payment_started');
        }

        $transaction->update([
            'status' => 'pending',
            'provider_order_id' => $dto->providerOrderId,
            'provider_redirect_url' => $dto->redirectUrl ?: null,
        ]);

        return response()->json(['status' => 'pending']);
    }

    /**
     * POST /pay/{uuid}/bank — app2app przez pay-by-link wybranego banku.
     * Zwraca redirect, który na telefonie otwiera aplikację banku.
     */
    public function bank(Request $request, string $uuid)
    {
        $transaction = Transaction::findOrFail($uuid);

        if ($transaction->isFinal()) {
            return response()->json(['status' => $transaction->status, 'redirect' => route('pay.return', $transaction->id)]);
        }

        // Zamówienie już istnieje (np. powrót bez dokończenia) — kontynuuj.
        if ($transaction->status === 'pending' && $transaction->provider_redirect_url) {
            return response()->json(['status' => 'pending', 'redirect' => $transaction->provider_redirect_url]);
        }

        $allowed = collect($this->provider instanceof \App\Modules\Gateway\Payments\PayUProvider ? $this->provider->payByLinks() : [])
            ->pluck('value')->all();

        $data = $request->validate([
            'method' => ['required', 'string', \Illuminate\Validation\Rule::in($allowed)],
        ], ['method.in' => 'Wybierz bank z listy.', 'method.required' => 'Wybierz bank.']);

        try {
            $dto = $this->provider->createTransaction($transaction, $request->ip(), [
                'pbl' => $data['method'],
            ]);
        } catch (\Throwable $e) {
            Log::error('PBL: utworzenie płatności nieudane', [
                'transaction' => $transaction->id,
                'method' => $data['method'],
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Nie udało się rozpocząć płatności. Spróbuj ponownie.'], 422);
        }

        if ($transaction->status === 'created') {
            $this->transactions->logEvent($transaction, 'payment_started');
        }

        $transaction->update([
            'status' => 'pending',
            'provider_order_id' => $dto->providerOrderId,
            'provider_redirect_url' => $dto->redirectUrl,
        ]);

        return response()->json(['status' => 'pending', 'redirect' => $dto->redirectUrl]);
    }

    /**
     * POST /pay/{uuid}/online — klasyczna platnosc online (przelew/karta):
     * tworzy standardowe zamowienie PayU bez wymuszonej metody i przekierowuje
     * klienta na hostowana strone PayU z wyborem banku/karty.
     */
    public function online(Request $request, string $uuid)
    {
        $transaction = Transaction::findOrFail($uuid);

        if ($transaction->isFinal()) {
            return redirect()->route('pay.return', $transaction->id);
        }

        // Zamowienie juz istnieje (powrot bez dokonczenia) - kontynuuj.
        if ($transaction->status === 'pending' && $transaction->provider_redirect_url) {
            return redirect()->away($transaction->provider_redirect_url);
        }

        try {
            $dto = $this->provider->createTransaction($transaction, $request->ip(), [
                'classic' => true,
            ]);
        } catch (\Throwable $e) {
            Log::error('Platnosc online: utworzenie nieudane', [
                'transaction' => $transaction->id,
                'error' => $e->getMessage(),
            ]);

            return response()->view('payment.error', ['transaction' => $transaction], 502);
        }

        if ($transaction->status === 'created') {
            $this->transactions->logEvent($transaction, 'payment_started');
        }

        $transaction->update([
            'status' => 'pending',
            'provider_order_id' => $dto->providerOrderId,
            'provider_redirect_url' => $dto->redirectUrl,
        ]);

        return redirect()->away($dto->redirectUrl);
    }

    /**
     * GET /pay/{uuid}/status — polling statusu z hostowanej strony BLIK.
     */
    public function status(string $uuid)
    {
        $transaction = Transaction::findOrFail($uuid);

        $this->transactions->reconcileWithProvider($transaction);

        return response()->json([
            'status' => $transaction->status,
            'redirect' => $transaction->isFinal() ? route('pay.return', $transaction->id) : null,
        ]);
    }

    /**
     * GET /pay/{uuid}/return — continueUrl operatora ("powrót przez bramkę").
     * Odsyła klienta na return_url sklepu; sklep i tak weryfikuje status API.
     */
    public function return(string $uuid)
    {
        $transaction = Transaction::findOrFail($uuid);

        return redirect()->away($transaction->return_url);
    }
}
