<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Models\JobPosition;

class CareersController extends Controller
{
    /**
     * GET /praca — lista aktywnych stanowisk pracy.
     */
    public function index()
    {
        $positions = JobPosition::where('active', true)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return view('shop.praca', compact('positions'));
    }
}
