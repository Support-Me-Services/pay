<?php

namespace App\Modules\Gateway\Http\Middleware;

use App\Modules\Gateway\Models\Shop;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-Api-Key');

        $shop = $apiKey ? Shop::where('api_key', $apiKey)->first() : null;

        if (! $shop) {
            return response()->json(['error' => 'Niepoprawny klucz API'], 401);
        }

        $request->attributes->set('shop', $shop);

        return $next($request);
    }
}
