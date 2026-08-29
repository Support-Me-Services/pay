<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Models\BeneficiaryNode;
use App\Modules\Storefront\Models\Organization;
use Inertia\Inertia;

/**
 * Podstrona „O nas" per-organizacja (/people/{handle}/wspieramy) — odpowiednik
 * globalnej BeneficiariesController, scoped przez organizację wskazaną handle.
 */
class UserBeneficiariesController extends Controller
{
    public function index(string $handle)
    {
        $org = Organization::where('handle', $handle)->firstOrFail();
        $nodes = BeneficiaryNode::forOrganization($org->id)->active()->ordered()->get();

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
            'pageTitle' => 'O nas — ' . $org->name,
            'pageDescription' => 'Kogo i jak wspiera ' . $org->name . ' — SupportMe łączy ludzi, wartości i nowoczesne płatności.',
        ]);
    }
}
