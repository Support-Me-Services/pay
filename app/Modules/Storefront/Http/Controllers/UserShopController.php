<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Models\Organization;
use App\Modules\Storefront\Models\ShopItem;
use Inertia\Inertia;

/**
 * Sklep per‑organizacja pod /people/{handle} — model SKLEPOWY (siatka, stała
 * cena, koszyk). Produkty należą do organizacji wskazanej przez handle.
 */
class UserShopController extends Controller
{
    public function index(string $handle)
    {
        $org = Organization::where('handle', $handle)->firstOrFail();
        $items = ShopItem::forOrganization($org->id)->where('active', true)->ordered()->get();

        return Inertia::render('Storefront/UserShop', [
            'items' => $items->map(fn (ShopItem $i) => [
                'id' => $i->id,
                'name' => $i->name,
                'description' => $i->description,
                'image' => $i->image ? asset($i->image) : null,
                'is_svg' => $i->isSvg(),
                'price' => $i->pricePln(),
                'add_url' => route('user.cart.add', [$handle, $i->id]),
            ])->values(),
            'ownerName' => $org->name,
            'shopHandle' => $handle,
            'cartCount' => array_sum((array) session("cart.$handle", [])),
            'pageTitle' => 'Zbiórki — ' . $org->name,
            'pageDescription' => 'Sklep ' . $org->name . ' — gadżety i tagi NFC. Dodaj do koszyka i zapłać online.',
        ]);
    }
}
