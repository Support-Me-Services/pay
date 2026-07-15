<?php

namespace App\Modules\Gateway\Http\Controllers\Panel;

use App\Modules\Gateway\Http\Controllers\Controller;
use App\Modules\Gateway\Models\AntitheftCheck;
use App\Modules\Gateway\Models\Shop;
use App\Modules\Gateway\Models\Tag;
use Illuminate\Http\Request;
use Inertia\Inertia;

// FIKCYJNE — moduł demo, brak realnej detekcji.
// Status NIGDY nie zmienia się na warning; "ostatnie sprawdzenie" jest losowane.
class AntiTheftController extends Controller
{
    public function index(Request $request)
    {
        $shops = Shop::orderBy('name')->get(['id', 'name']);
        $shop = $request->integer('shop_id')
            ? $shops->firstWhere('id', $request->integer('shop_id'))
            : $shops->first();

        $tags = $shop ? Tag::where('shop_id', $shop->id)->get(['id', 'tag_uid', 'label']) : collect();

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

        return Inertia::render('Gateway/AntiTheft', [
            'shops' => $shops,
            'shop' => $shop ? ['id' => $shop->id, 'name' => $shop->name] : null,
            'tags' => $tags->map(fn (Tag $t) => ['id' => $t->id, 'tag_uid' => $t->tag_uid, 'label' => $t->label])->values(),
            'lastCheck' => $shop ? [
                'at' => $lastCheck->format('d.m.Y H:i'),
                'human' => $lastCheck->diffForHumans(),
            ] : null,
            'antitheftUrl' => route('panel.antitheft'),
        ]);
    }
}
