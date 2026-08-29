<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Models\Order;
use App\Modules\Storefront\Services\GatewayClient;
use Inertia\Inertia;

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
            return redirect()->route('main', ['thank-you-page' => 1]);
        }

        $order = Order::findOrFail($orderId);

        $this->syncStatusFromGateway($order);

        if ($order->status === 'paid') {
            // Podziekowanie pokazujemy jako modal na stronie glownej (/main),
            // ze wlasna trescia danego produktu, jesli znane (shop_item_id).
            return redirect()->route('main', array_filter(['thank-you-page' => 1, 'item' => $order->shop_item_id]));
        }

        if ($order->status === 'failed') {
            $org = $order->shopItem?->organization;

            return Inertia::render('Storefront/ReturnFailure', [
                'orderId' => $order->id,
                'retryUrl' => $org ? route('user.shop', $org->handle) : route('home'),
                'cancelUrl' => route('main'),
            ]);
        }

        // Płatność jeszcze nieprzetworzona (np. webhook PayU w drodze) — ekran
        // oczekiwania z odpytywaniem statusu.
        return Inertia::render('Storefront/ReturnPending', [
            'orderId' => $order->id,
            'statusUrl' => route('order.status', $order->id),
        ]);
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
        } elseif (in_array($transaction['status'], ['failed', 'abandoned'])) {
            $order->update(['status' => 'failed']);
        }
    }
}
