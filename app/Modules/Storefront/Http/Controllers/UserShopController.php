<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Models\User;
use App\Modules\Storefront\Models\ShopItem;

/**
 * Sklep per‑konto pod /user/{handle} — model SKLEPOWY (siatka, stała cena,
 * koszyk). Produkty należą do właściciela wskazanego przez handle.
 */
class UserShopController extends Controller
{
    public function index(string $handle)
    {
        $owner = User::where('handle', $handle)->firstOrFail();
        $items = ShopItem::forUser($owner->id)->where('active', true)->ordered()->get();

        return view('shop.user-shop', [
            'owner' => $owner,
            'shopHandle' => $handle,
            'items' => $items,
        ]);
    }
}
