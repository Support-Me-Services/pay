<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Models\Organization;
use App\Services\OrgSvcClient;
use Inertia\Inertia;

/**
 * Sklep per‑organizacja pod /people/{handle} — model SKLEPOWY (siatka, stała
 * cena, koszyk). Produkty należą do organizacji wskazanej przez handle.
 */
class UserShopController extends Controller
{
    public function index(string $handle)
    {
        $org = Organization::findByHandleOrFail($handle);
        $items = app(OrgSvcClient::class)->listShopItems($org->id, activeOnly: true);
        usort($items, fn ($a, $b) => $a['sort'] <=> $b['sort']);

        return Inertia::render('Storefront/UserShop', [
            'items' => array_values(array_map(fn (array $i) => [
                'id' => $i['id'],
                'name' => $i['name'],
                'description' => $i['description'],
                'image' => $i['image'] ? asset($i['image']) : null,
                'is_svg' => $i['image'] && str_ends_with(strtolower($i['image']), '.svg'),
                'price' => (int) round($i['priceGrosze'] / 100),
                'add_url' => route('user.cart.add', [$handle, $i['id']]),
            ], $items)),
            'ownerName' => $org->name,
            'shopHandle' => $handle,
            'cartCount' => array_sum((array) session("cart.$handle", [])),
            'pageTitle' => 'Zbiórki — ' . $org->name,
            'pageDescription' => 'Sklep ' . $org->name . ' — gadżety i tagi NFC. Dodaj do koszyka i zapłać online.',
        ]);
    }
}
