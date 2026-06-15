<?php

namespace App\Modules\Gateway\Http\Controllers\Api;

use App\Modules\Gateway\Http\Controllers\Controller;
use App\Modules\Gateway\Models\Shop;
use App\Modules\Gateway\Models\Tag;
use App\Modules\Gateway\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * POST /api/v1/transactions — sklep tworzy transakcję, dostaje payment_url.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $data = $request->validate([
            'product_external_id' => ['required', 'string', 'max:255'],
            'product_name' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'integer', 'min:1'], // grosze
            'currency' => ['nullable', 'string', 'in:PLN'],
            'return_url' => ['required', 'url', 'max:500'],
            'notify_url' => ['nullable', 'url', 'max:500'],
            'tag_uid' => ['nullable', 'string', 'max:255'],
        ]);

        $tag = null;
        if (! empty($data['tag_uid'])) {
            $tag = Tag::where('shop_id', $shop->id)->where('tag_uid', $data['tag_uid'])->first();
        }

        $transaction = Transaction::create([
            'shop_id' => $shop->id,
            'tag_id' => $tag?->id,
            'product_external_id' => $data['product_external_id'],
            'product_name' => $data['product_name'],
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? 'PLN',
            'status' => 'created',
            'mode' => $shop->payment_mode,
            'return_url' => $data['return_url'],
            'notify_url' => $data['notify_url'] ?? null,
        ]);

        return response()->json([
            'uuid' => $transaction->id,
            'payment_url' => route('pay.show', $transaction->id),
        ], 201);
    }

    /**
     * GET /api/v1/transactions/{uuid} — sklep weryfikuje status (nie ufa redirectowi).
     */
    public function show(Request $request, string $uuid): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $transaction = Transaction::where('id', $uuid)->where('shop_id', $shop->id)->first();

        if (! $transaction) {
            return response()->json(['error' => 'Transakcja nie istnieje'], 404);
        }

        // Sklep polluje ten endpoint z ekranu "sprawdzamy płatność" —
        // przy okazji aktywnie weryfikujemy status u operatora.
        app(\App\Modules\Gateway\Services\TransactionService::class)->reconcileWithProvider($transaction);

        return response()->json([
            'uuid' => $transaction->id,
            'status' => $transaction->status,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'mode' => $transaction->mode,
            'paid_at' => $transaction->paid_at?->toIso8601String(),
            'created_at' => $transaction->created_at?->toIso8601String(),
        ]);
    }
}
