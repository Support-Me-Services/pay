<?php

namespace App\Modules\Gateway\Http\Controllers\Panel;

use App\Modules\Gateway\Http\Controllers\Controller;
use App\Modules\Gateway\Models\Shop;
use App\Modules\Gateway\Models\Tag;
use App\Modules\Gateway\Services\StatsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TagController extends Controller
{
    public function index(Shop $shop, StatsService $stats)
    {
        $tags = $shop->tags()->get()->map(function (Tag $tag) use ($shop, $stats) {
            $s = $stats->summary(shopId: $shop->id, tagId: $tag->id);

            return [
                'id' => $tag->id,
                'tag_uid' => $tag->tag_uid,
                'label' => $tag->label,
                'target_url' => $tag->target_url,
                'active' => (bool) $tag->active,
                'opens' => $s['opens'],
                'paid' => $s['paid'],
                'edit_url' => route('panel.tags.edit', [$shop, $tag]),
                'stats_url' => route('panel.stats', ['shop_id' => $shop->id, 'tag_id' => $tag->id]),
            ];
        })->values();

        return Inertia::render('Gateway/Tags/Index', [
            'shop' => ['id' => $shop->id, 'name' => $shop->name],
            'tags' => $tags,
            'createUrl' => route('panel.tags.create', $shop),
        ]);
    }

    public function create(Shop $shop)
    {
        return $this->form($shop, new Tag());
    }

    /** Wspólny render formularza taga (Inertia). */
    private function form(Shop $shop, Tag $tag)
    {
        return Inertia::render('Gateway/Tags/Form', [
            'shop' => ['id' => $shop->id, 'name' => $shop->name, 'base_url' => $shop->base_url],
            'tag' => [
                'exists' => $tag->exists,
                'tag_uid' => $tag->tag_uid,
                'label' => $tag->label,
                'target_url' => $tag->target_url,
                'active' => $tag->exists ? (bool) $tag->active : true,
            ],
            'urls' => [
                'store' => route('panel.tags.store', $shop),
                'update' => $tag->exists ? route('panel.tags.update', [$shop, $tag]) : null,
                'index' => route('panel.tags.index', $shop),
            ],
        ]);
    }

    public function store(Request $request, Shop $shop)
    {
        $data = $this->validated($request);

        $shop->tags()->create($data);

        return redirect()->route('panel.tags.index', $shop)->with('success', 'Tag dodany.');
    }

    public function edit(Shop $shop, Tag $tag)
    {
        abort_unless($tag->shop_id === $shop->id, 404);

        return $this->form($shop, $tag);
    }

    public function update(Request $request, Shop $shop, Tag $tag)
    {
        abort_unless($tag->shop_id === $shop->id, 404);

        $tag->update($this->validated($request, $tag));

        return redirect()->route('panel.tags.index', $shop)->with('success', 'Tag zapisany.');
    }

    private function validated(Request $request, ?Tag $tag = null): array
    {
        return $request->validate([
            'tag_uid' => ['required', 'string', 'max:255', Rule::unique('tags', 'tag_uid')->ignore($tag?->id)],
            'label' => ['nullable', 'string', 'max:255'],
            'target_url' => ['required', 'url', 'max:255'],
            'active' => ['nullable', 'boolean'],
        ], [], ['tag_uid' => 'UID taga', 'label' => 'etykieta', 'target_url' => 'docelowy URL'])
            + ['active' => $request->boolean('active')];
    }
}
