<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Models\BeneficiaryNode;
use App\Modules\Storefront\Models\Organization;
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
        $nodes = $org
            ? BeneficiaryNode::forOrganization($org->id)->active()->ordered()->get()
            : collect();

        return Inertia::render('Storefront/Beneficiaries', [
            'nodes' => $nodes->map(fn (BeneficiaryNode $n) => [
                'id' => $n->id,
                'heading' => $n->heading,
                'text_align' => $n->text_align,
                'image' => $n->image ? asset('storage/' . $n->image) : null,
                'image_x' => $n->image_x,
                'image_y' => $n->image_y,
                'image_scale' => $n->image_scale,
                'image_right' => $n->imageRight(),
                'body_html' => $n->body_html ?? '',
            ])->values(),
            'pageTitle' => 'O nas — ' . config('shop.name'),
            'pageDescription' => 'Kogo i jak wspieramy — SupportMe łączy ludzi, wartości i nowoczesne płatności.',
        ]);
    }
}
