<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Models\Event;
use App\Modules\Storefront\Models\Order;
use App\Modules\Storefront\Services\GatewayClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GatewayWebhookController extends Controller
{
    /**
     * POST /webhooks/gateway — webhook bramki {uuid, status, paid_at},
     * podpis HMAC-SHA256 z api_key w nagłówku X-Signature.
     */
    public function handle(Request $request, GatewayClient $gateway)
    {
        $body = $request->getContent();

        if (! $gateway->verifyWebhookSignature($body, $request->header('X-Signature', ''))) {
            Log::warning('Webhook bramki: niepoprawny podpis', ['ip' => $request->ip()]);

            return response()->json(['error' => 'Niepoprawny podpis'], 401);
        }

        $uuid = $request->json('uuid');
        $status = $request->json('status');

        $order = $uuid ? Order::where('transaction_id', $uuid)->first() : null;

        if ($order && $order->status === 'pending') {
            if ($status === 'paid') {
                $order->update(['status' => 'paid', 'paid_at' => $request->json('paid_at') ?? now()]);
                Event::create(['product_id' => $order->product_id, 'type' => 'purchase']);
            } elseif (in_array($status, ['failed', 'abandoned'])) {
                $order->update(['status' => 'failed']);
            }
        }

        return response()->json(['ok' => true]);
    }
}
