<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CategoryController extends Controller
{
    /**
     * Lista kategorii jako drzewo (po position, z wcięciami wg parent).
     */
    public function index()
    {
        // Wszystkie kategorie raz; drzewo budujemy w pamięci.
        $all = Category::ordered()->get();
        $tree = $this->buildTree($all);

        return Inertia::render('Panel/Categories/Index', [
            'items' => collect($tree)->map(fn ($row) => $this->present($row['cat']) + ['depth' => $row['depth']])->values(),
            'createUrl' => route('panel.categories.create'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Panel/Categories/Form', [
            'item' => null,
            'parentOptions' => $this->parentOptionsList(),
            'sourceOptions' => $this->sourceOptions(),
            'storeUrl' => route('panel.categories.store'),
            'indexUrl' => route('panel.categories.index'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $data['icon'] = $this->storeIcon($request);

        Category::create($data);

        return redirect()->route('panel.categories.index')->with('success', 'Kategoria dodana.');
    }

    public function edit(Category $category)
    {
        return Inertia::render('Panel/Categories/Form', [
            'item' => $this->present($category),
            'parentOptions' => $this->parentOptionsList($category),
            'sourceOptions' => $this->sourceOptions(),
            'storeUrl' => route('panel.categories.store'),
            'indexUrl' => route('panel.categories.index'),
        ]);
    }

    /** Serializacja kategorii dla React (Inertia). */
    private function present(Category $cat): array
    {
        return [
            'id' => $cat->id,
            'parent_id' => $cat->parent_id,
            'label' => $cat->label,
            'label_text' => $cat->label_text,
            'label_html' => $cat->label_html,
            'slug' => $cat->slug,
            'intro' => $cat->intro,
            'source' => $cat->source,
            'source_label' => $cat->sourceLabel(),
            'position' => (int) $cat->position,
            'active' => (bool) $cat->active,
            'icon' => $cat->icon ? asset('storage/'.$cat->icon) : null,
            'edit_url' => route('panel.categories.edit', $cat),
            'update_url' => route('panel.categories.update', $cat),
            'destroy_url' => route('panel.categories.destroy', $cat),
            'reorder_url' => route('panel.categories.reorder', $cat),
        ];
    }

    /** Opcje rodzica jako lista {value,label} (z wcięciami). */
    private function parentOptionsList(?Category $current = null): array
    {
        $out = [];
        foreach ($this->parentOptions($current) as $id => $label) {
            $out[] = ['value' => $id, 'label' => $label];
        }

        return $out;
    }

    /** Opcje źródła jako lista {value,label}. */
    private function sourceOptions(): array
    {
        $out = [];
        foreach (Category::SOURCES as $key => $label) {
            $out[] = ['value' => $key, 'label' => $label];
        }

        return $out;
    }

    public function update(Request $request, Category $category)
    {
        $data = $this->validated($request, $category);

        if ($icon = $this->storeIcon($request)) {
            // Kasujemy starą ikonkę przy podmianie.
            if ($category->icon) {
                Storage::disk('public')->delete($category->icon);
            }
            $data['icon'] = $icon;
        }

        $category->update($data);

        return redirect()->route('panel.categories.index')->with('success', 'Kategoria zapisana.');
    }

    public function destroy(Category $category)
    {
        // FK nullOnDelete — dzieci stają się top-level, nie znikają.
        if ($category->icon) {
            Storage::disk('public')->delete($category->icon);
        }

        $category->delete();

        return redirect()->route('panel.categories.index')->with('success', 'Kategoria usunięta.');
    }

    /**
     * Przesunięcie kategorii w górę/dół wśród rodzeństwa (zamiana position).
     */
    public function reorder(Request $request, Category $category)
    {
        $dir = $request->input('direction') === 'up' ? 'up' : 'down';

        $siblingsQuery = Category::query()
            ->where('parent_id', $category->parent_id)
            ->whereKeyNot($category->id);

        $neighbour = $dir === 'up'
            ? $siblingsQuery->where('position', '<=', $category->position)
                ->orderByDesc('position')->orderByDesc('id')->first()
            : $siblingsQuery->where('position', '>=', $category->position)
                ->orderBy('position')->orderBy('id')->first();

        if ($neighbour) {
            $pos = $category->position;
            $category->update(['position' => $neighbour->position]);
            $neighbour->update(['position' => $pos]);
        }

        return redirect()->route('panel.categories.index');
    }

    /**
     * Walidacja + normalizacja danych formularza.
     */
    private function validated(Request $request, ?Category $category = null): array
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where(
                fn ($q) => $category ? $q->whereKeyNot($category->id) : $q
            )],
            'label' => ['required', 'string', 'max:255'],
            'label_html' => ['nullable', 'string', 'max:1000'],
            'label_text' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('categories', 'slug')->ignore($category?->id)],
            'intro' => ['nullable', 'string', 'max:2000'],
            'source' => ['required', 'string', 'in:' . implode(',', array_keys(Category::SOURCES))],
            'position' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'active' => ['nullable', 'boolean'],
            'icon' => ['nullable', 'image', 'max:8192'],
        ], [], [
            'parent_id' => 'rodzic', 'label' => 'nazwa', 'label_html' => 'nazwa (HTML)',
            'label_text' => 'nazwa (tekst)', 'slug' => 'slug', 'intro' => 'opis',
            'source' => 'źródło pozycji', 'position' => 'kolejność', 'icon' => 'ikonka',
        ]);

        // label_text domyślnie = label; label_html domyślnie = escaped label_text.
        $labelText = trim((string) ($data['label_text'] ?? '')) ?: $data['label'];
        $labelHtml = trim((string) ($data['label_html'] ?? '')) ?: e($labelText);

        // Slug auto z label gdy puste; unikalność zapewniamy iteracyjnie.
        $slug = trim((string) ($data['slug'] ?? '')) ?: Str::slug($data['label']);
        $slug = $this->uniqueSlug($slug, $category);

        return [
            'parent_id' => ($data['parent_id'] ?? null) ?: null,
            'label' => $data['label'],
            'label_html' => $labelHtml,
            'label_text' => $labelText,
            'slug' => $slug,
            'intro' => $data['intro'] ?? null,
            'source' => $data['source'],
            'position' => (int) ($data['position'] ?? 0),
            'active' => $request->boolean('active'),
        ];
    }

    private function uniqueSlug(string $base, ?Category $category = null): string
    {
        $base = Str::slug($base) ?: 'kategoria';
        $slug = $base;
        $i = 2;
        while (Category::where('slug', $slug)->when($category, fn ($q) => $q->whereKeyNot($category->id))->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function storeIcon(Request $request): ?string
    {
        if (! $request->hasFile('icon')) {
            return null;
        }

        return $request->file('icon')->store('category-icons', 'public');
    }

    /**
     * Opcje selecta rodzica — z wcięciami wg drzewa, z pominięciem edytowanej
     * kategorii i jej potomków (zapobiega cyklom).
     *
     * @return array<int,string>  id => etykieta z wcięciem
     */
    private function parentOptions(?Category $current = null): array
    {
        $all = Category::ordered()->get();
        $excluded = $current ? $this->descendantIds($all, $current->id) : [];

        $options = [];
        $walk = function ($parentId, $depth) use (&$walk, $all, $excluded, &$options) {
            foreach ($all->where('parent_id', $parentId) as $cat) {
                if (in_array($cat->id, $excluded, true)) {
                    continue;
                }
                $options[$cat->id] = str_repeat('— ', $depth) . $cat->label_text;
                $walk($cat->id, $depth + 1);
            }
        };
        $walk(null, 0);

        return $options;
    }

    /**
     * ID kategorii + wszystkich jej potomków.
     *
     * @return list<int>
     */
    private function descendantIds($all, int $id): array
    {
        $ids = [$id];
        foreach ($all->where('parent_id', $id) as $child) {
            $ids = array_merge($ids, $this->descendantIds($all, $child->id));
        }

        return $ids;
    }

    /**
     * Spłaszczone drzewo do widoku listy: każdy element ['cat' => Category, 'depth' => int].
     *
     * @return list<array{cat:Category,depth:int}>
     */
    private function buildTree($all, $parentId = null, int $depth = 0): array
    {
        $rows = [];
        foreach ($all->where('parent_id', $parentId) as $cat) {
            $rows[] = ['cat' => $cat, 'depth' => $depth];
            $rows = array_merge($rows, $this->buildTree($all, $cat->id, $depth + 1));
        }

        return $rows;
    }
}
