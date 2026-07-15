<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Models\BeneficiaryNode;
use Inertia\Inertia;

/**
 * Publiczna podstrona „Wspieramy" (/beneficiaries) — lista węzłów z panelu.
 */
class BeneficiariesController extends Controller
{
    public function index()
    {
        $nodes = BeneficiaryNode::active()->ordered()->get();

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
            'pageTitle' => 'Wspieramy — ' . config('shop.name'),
            'pageDescription' => 'Kogo i jak wspieramy — SupportMe łączy ludzi, wartości i nowoczesne płatności.',
        ]);
    }
}
