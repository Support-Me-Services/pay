<?php

namespace App\Modules\Gateway\Http\Controllers\Panel;

use App\Modules\Gateway\Http\Controllers\Controller;
use App\Modules\Gateway\Models\AntitheftCheck;
use App\Modules\Gateway\Models\Shop;
use Illuminate\Http\Request;

// FIKCYJNE — moduł demo, brak realnej detekcji.
// Status NIGDY nie zmienia się na warning; "ostatnie sprawdzenie" jest losowane.
class AntiTheftController extends Controller
{
    public function index(Request $request)
    {
        $shops = Shop::orderBy('name')->get();
        $shop = $request->integer('shop_id')
            ? $shops->firstWhere('id', $request->integer('shop_id'))
            : $shops->first();

        $tags = $shop ? $shop->tags()->get() : collect();

        // FIKCYJNE — losowa data ostatniego sprawdzenia: 5–45 min temu
        $lastCheck = now()->subMinutes(random_int(5, 45));

        if ($shop) {
            AntitheftCheck::create([
                'shop_id' => $shop->id,
                'status' => 'ok',
                'foreign_tags_found' => 0,
                'checked_at' => $lastCheck,
            ]);
        }

        return view('panel.antitheft', compact('shops', 'shop', 'tags', 'lastCheck'));
    }
}
