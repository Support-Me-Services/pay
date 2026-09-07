<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Models\Organization;
use App\Services\OrgSvcClient;
use Inertia\Inertia;

/**
 * Publiczna podstrona „O nas" (/beneficiaries) — lista węzłów organizacji
 * głównej (root organization). Odpowiednik per-organizacja:
 * UserBeneficiariesController pod /people/{handle}/wspieramy.
 */
class BeneficiariesController extends Controller
{
    public function index()
    {
        $org = Organization::rootOrganization();
        $nodes = $org ? app(OrgSvcClient::class)->listBeneficiaryNodes($org->id, activeOnly: true) : [];
        usort($nodes, fn ($a, $b) => $a['position'] <=> $b['position']);

        return Inertia::render('Storefront/Beneficiaries', [
            'nodes' => array_values(array_map(fn (array $n) => [
                'id' => $n['id'],
                'heading' => $n['heading'],
                'text_align' => $n['textAlign'],
                'image' => $n['image'] ? asset('storage/' . $n['image']) : null,
                'image_x' => $n['imageX'],
                'image_y' => $n['imageY'],
                'image_scale' => $n['imageScale'],
                'image_right' => $n['imageSide'] === 'right',
                'body_html' => $n['bodyHtml'] ?? '',
            ], $nodes)),
            'pageTitle' => 'O nas — ' . config('shop.name'),
            'pageDescription' => 'Kogo i jak wspieramy — SupportMe łączy ludzi, wartości i nowoczesne płatności.',
        ]);
    }
}
