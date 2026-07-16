<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Models\User;
use App\Modules\Storefront\Models\ShopItem;
use Inertia\Inertia;

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

        return Inertia::render('Storefront/UserShop', [
            'items' => $items->map(fn (ShopItem $i) => [
                'id' => $i->id,
                'name' => $i->name,
                'description' => $i->description,
                'image' => asset($i->image),
                'is_svg' => $i->isSvg(),
                'price' => $i->pricePln(),
                'add_url' => route('user.cart.add', [$handle, $i->id]),
            ])->values(),
            'ownerName' => $owner->name,
            'shopHandle' => $handle,
            'cartCount' => array_sum((array) session("cart.$handle", [])),
            'pageTitle' => 'Sklep — ' . $owner->name,
            'pageDescription' => 'Sklep ' . $owner->name . ' — gadżety i tagi NFC. Dodaj do koszyka i zapłać online.',
        ]);
    }
}
