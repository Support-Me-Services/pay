<?php

namespace App\Modules\Gateway\Http\Controllers;

use App\Modules\Gateway\Models\Transaction;
use App\Modules\Gateway\Services\TransactionService;
use Illuminate\Http\Request;

/**
 * Hostowane strony płatności MockProvidera (dev/demo).
 * classic: pole na 6-cyfrowy kod (dowolny = sukces po 2 s);
 * app2app: animacja "aplikacji banku", sukces po 3 s.
 */
class MockPaymentController extends Controller
{
    public function __construct(private readonly TransactionService $transactions)
    {
    }

    public function show(string $uuid)
    {
        $transaction = Transaction::findOrFail($uuid);

        if ($transaction->isFinal()) {
            return redirect()->away($transaction->return_url);
        }

        $view = $transaction->mode === 'app2app' ? 'mockpay.app2app' : 'mockpay.classic';

        return view($view, ['transaction' => $transaction]);
    }

    public function confirm(Request $request, string $uuid)
    {
        $transaction = Transaction::findOrFail($uuid);

        $this->transactions->markPaid($transaction);

        return response()->json(['redirect' => route('pay.return', $transaction->id)]);
    }

    public function fail(Request $request, string $uuid)
    {
        $transaction = Transaction::findOrFail($uuid);

        $this->transactions->markFailed($transaction);

        return response()->json(['redirect' => route('pay.return', $transaction->id)]);
    }
}
