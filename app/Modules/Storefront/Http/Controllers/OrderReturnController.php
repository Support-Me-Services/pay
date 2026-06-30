<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Models\Event;
use App\Modules\Storefront\Models\Order;
use App\Modules\Storefront\Services\GatewayClient;

class OrderReturnController extends Controller
{
    public function __construct(private readonly GatewayClient $gateway)
    {
    }

    /**
     * GET /zwrot/{order} — ekran "ZABIERZ TOWAR". Status weryfikowany
     * odpytaniem API bramki (nie ufamy samemu redirectowi).
     */
    public function show(string $orderId)
    {
        // PayU podpiete, ale pomijamy weryfikacje statusu - od razu podziekowanie.
        if (config('payment.return_bypass')) {
            return redirect()->route('main', ['dzieki' => 1]);
        }

        $order = Order::with('product.images')->findOrFail($orderId);

        $this->syncStatusFromGateway($order);

        if ($order->status === 'paid') {
            $product = $order->product;

            // Sklep parafialny (Taca) nie ma odbioru towaru — pomijamy logikę pickup.
            if (config('platform.shop_kind') === 'church') {
                // Podziekowanie pokazujemy jako modal na stronie glownej (/main).
                return redirect()->route('main', ['dzieki' => 1]);
            }

            // Animacja linki dla produktów zabezpieczonych linką;
            // numer stanowiska dla instrukcji wskazujących stojak.
            $instruction = (string) $product->pickup_instruction;
            $showTether = str_contains(mb_strtolower($instruction), 'link');
            $standNo = (str_contains($instruction, 'stojaka nr') || str_contains(mb_strtolower($instruction), 'stanowis'))
                ? random_int(1, 6)
                : null;

            return view('shop.return-success', compact('order', 'product', 'showTether', 'standNo'));
        }

        if ($order->status === 'failed') {
            return view('shop.return-failure', compact('order'));
        }

        // Płatność jeszcze nieprzetworzona (np. webhook PayU w drodze) — ekran
        // oczekiwania z odpytywaniem statusu.
        return view('shop.return-pending', compact('order'));
    }

    /**
     * GET /zwrot/{order}/status — JSON do pollingu z ekranu oczekiwania.
     */
    public function status(string $orderId)
    {
        $order = Order::findOrFail($orderId);

        $this->syncStatusFromGateway($order);

        return response()->json(['status' => $order->status]);
    }

    /**
     * Pobiera status z bramki i aktualizuje zamówienie (idempotentnie).
     * Event purchase logowany wyłącznie przy przejściu pending → paid.
     */
    private function syncStatusFromGateway(Order $order): void
    {
        if ($order->status !== 'pending' || ! $order->transaction_id) {
            return;
        }

        $transaction = $this->gateway->getTransaction($order->transaction_id);

        if (! $transaction) {
            return;
        }

        if ($transaction['status'] === 'paid') {
            $order->update(['status' => 'paid', 'paid_at' => $transaction['paid_at'] ?? now()]);
            Event::create(['product_id' => $order->product_id, 'type' => 'purchase']);
        } elseif (in_array($transaction['status'], ['failed', 'abandoned'])) {
            $order->update(['status' => 'failed']);
        }
    }
}
