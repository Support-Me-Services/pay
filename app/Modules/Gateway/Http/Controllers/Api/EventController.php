<?php

namespace App\Modules\Gateway\Http\Controllers\Api;

use App\Modules\Gateway\Http\Controllers\Controller;
use App\Modules\Gateway\Models\Event;
use App\Modules\Gateway\Models\Shop;
use App\Modules\Gateway\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * POST /api/v1/events — sklepy raportują eventy (tag_open).
     */
    public function store(Request $request): JsonResponse
    {
        /** @var Shop $shop */
        $shop = $request->attributes->get('shop');

        $data = $request->validate([
            'type' => ['required', 'in:tag_open'],
            'tag_uid' => ['nullable', 'string', 'max:255'],
        ]);

        $tag = null;
        if (! empty($data['tag_uid'])) {
            $tag = Tag::where('shop_id', $shop->id)->where('tag_uid', $data['tag_uid'])->first();
        }

        Event::create([
            'shop_id' => $shop->id,
            'tag_id' => $tag?->id,
            'type' => $data['type'],
        ]);

        return response()->json(['ok' => true], 201);
    }
}
