<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Models\BeneficiaryNode;

/**
 * Publiczna podstrona „Wspieramy" (/beneficiaries) — lista węzłów z panelu.
 */
class BeneficiariesController extends Controller
{
    public function index()
    {
        $nodes = BeneficiaryNode::active()->ordered()->get();

        return view('shop.beneficiaries', compact('nodes'));
    }
}
