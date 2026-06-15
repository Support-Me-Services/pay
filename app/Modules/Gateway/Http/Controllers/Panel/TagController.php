<?php

namespace App\Modules\Gateway\Http\Controllers\Panel;

use App\Modules\Gateway\Http\Controllers\Controller;
use App\Modules\Gateway\Models\Shop;
use App\Modules\Gateway\Models\Tag;
use App\Modules\Gateway\Services\StatsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TagController extends Controller
{
    public function index(Shop $shop, StatsService $stats)
    {
        $tags = $shop->tags()->get()->map(fn (Tag $tag) => [
            'tag' => $tag,
            'stats' => $stats->summary(shopId: $shop->id, tagId: $tag->id),
        ]);

        return view('panel.tags.index', compact('shop', 'tags'));
    }

    public function create(Shop $shop)
    {
        return view('panel.tags.form', ['shop' => $shop, 'tag' => new Tag()]);
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

        return view('panel.tags.form', compact('shop', 'tag'));
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
