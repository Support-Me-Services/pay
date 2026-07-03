<?php

namespace App\Modules\Gateway\Http\Controllers;

use App\Modules\Gateway\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * Tokenowany endpoint dla zewnętrznego monitoringu aktywacji konta PayU.
 * Sygnał aktywacji: PayU provisionuje metody płatności na nowym POS-ie
 * (4433543, sklep zweryfikowany pod https://please-support-me.com).
 */
class ActivationStatusController extends Controller
{
    public function show(Request $request)
    {
        if (! hash_equals((string) config('payment.activation_check_token'), (string) $request->query('token'))) {
            return response()->json(['error' => 'unauthorized'], 401);
        }

        $newPos = ['methods' => null, 'blik_enabled' => null, 'error' => null];

        try {
            $auth = Http::asForm()->timeout(10)->post('https://secure.payu.com/pl/standard/user/oauth/authorize', [
                'grant_type' => 'client_credentials',
                'client_id' => config('payment.payu_newpos.client_id'),
                'client_secret' => config('payment.payu_newpos.client_secret'),
            ]);
            $token = $auth->json('access_token');

            if ($token) {
                $methods = Http::withToken($token)->acceptJson()->timeout(10)
                    ->get('https://secure.payu.com/api/v2_1/paymethods', ['lang' => 'pl'])
                    ->json('payByLinks', []);

                $enabled = array_values(array_filter($methods, fn ($m) => ($m['status'] ?? '') === 'ENABLED'));
                $newPos['methods'] = count($enabled);
                $newPos['blik_enabled'] = collect($enabled)->contains(fn ($m) => $m['value'] === 'blik');
            } else {
                $newPos['error'] = 'oauth_failed';
            }
        } catch (\Throwable $e) {
            $newPos['error'] = 'exception';
        }

        return response()->json([
            'checked_at' => now()->toIso8601String(),
            'new_pos' => $newPos,
            // czy realne płatności się księgują (przelewy z banków docierają)
            'paid_last_24h' => Transaction::where('status', 'paid')
                ->where('paid_at', '>', now()->subDay())->count(),
            'pending_last_24h' => Transaction::where('status', 'pending')
                ->where('created_at', '>', now()->subDay())->count(),
            'activation_likely_done' => ($newPos['methods'] ?? 0) > 0,
        ]);
    }
}
