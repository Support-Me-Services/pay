<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\ParishNote;
use App\Modules\Storefront\Models\Product;
use App\Modules\Storefront\Services\ShopStatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProductController extends Controller
{
    /**
     * Lista parafii z filtrem po statusie i wyszukiwarką (nazwa / miasto / województwo).
     */
    public function index(Request $request)
    {
        // Filtr statusu (zakładki) — tylko dozwolone klucze.
        $status = in_array($request->query('status'), array_keys(Product::STATUSES), true)
            ? $request->query('status') : null;
        $q = trim((string) $request->query('q', ''));

        $query = Product::withCount('orders')->orderBy('id');

        if ($status) {
            $query->where('status', $status);
        }
        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%")
                    ->orWhere('voivodeship', 'like', "%{$q}%");
            });
        }

        $parishes = $query->get();

        // Liczniki per status (niezależne od aktywnego filtra/wyszukiwania).
        $statusCounts = Product::query()
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
        $total = Product::count();

        // Zakładki-liczniki (filtr statusu), zachowując wyszukiwanie.
        $tabs = [[
            'key' => null, 'label' => 'Wszystkie', 'count' => $total,
            'url' => route('panel.products.index', array_filter(['q' => $q])), 'active' => ! $status,
        ]];
        foreach (Product::STATUSES as $key => $label) {
            $tabs[] = [
                'key' => $key, 'label' => $label, 'count' => (int) ($statusCounts[$key] ?? 0),
                'url' => route('panel.products.index', array_filter(['status' => $key, 'q' => $q])),
                'active' => $status === $key,
            ];
        }

        return Inertia::render('Panel/Products/Index', [
            'items' => $parishes->map(fn (Product $p) => $this->presentRow($p))->values(),
            'tabs' => $tabs,
            'q' => $q,
            'indexUrl' => route('panel.products.index'),
            'createUrl' => route('panel.products.create'),
        ]);
    }

    /** Serializacja wiersza listy parafii dla React. */
    private function presentRow(Product $p): array
    {
        [$bg, $fg] = $p->statusColors();

        return [
            'id' => $p->id,
            'name' => $p->name,
            'city' => $p->city,
            'voivodeship' => $p->voivodeship,
            'status_label' => $p->statusLabel(),
            'status_bg' => $bg,
            'status_fg' => $fg,
            'tag_uid' => $p->tag_uid,
            'orders_count' => $p->orders_count,
            'edit_url' => route('panel.products.edit', $p),
            'stats_url' => route('panel.products.stats', $p),
        ];
    }

    public function create()
    {
        return $this->form(new Product());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $product = Product::create([
            ...$data,
            'slug' => $this->uniqueSlug($data['name']),
            'main_image' => $this->storeMainImage($request),
        ]);

        $this->storeGallery($request, $product);

        return redirect()->route('panel.products.index')->with('success', 'Produkt dodany.');
    }

    public function edit(Product $product)
    {
        $product->load('notes', 'images');

        return $this->form($product);
    }

    /** Wspólny render formularza dodawania/edycji parafii (Inertia). */
    private function form(Product $product)
    {
        $exists = $product->exists;
        [$bg, $fg] = $product->statusColors();

        return Inertia::render('Panel/Products/Form', [
            'product' => [
                'id' => $product->id,
                'exists' => $exists,
                'name' => $product->name,
                'purpose' => $product->purpose,
                'city' => $product->city,
                'voivodeship' => $product->voivodeship,
                'phone' => $product->phone,
                'website' => $product->website,
                'status' => $product->status ?? 'kontakt',
                'status_label' => $product->statusLabel(),
                'status_bg' => $bg,
                'status_fg' => $fg,
                // Cena w formularzu w złotówkach (kropka dziesiętna), pusta dla nowej.
                'price' => $exists ? number_format($product->price / 100, 2, '.', '') : '',
                'tag_uid' => $product->tag_uid,
                'pickup_instruction' => $product->pickup_instruction,
                'description_html' => $product->description_html,
                'main_image_url' => $product->main_image ? asset('storage/' . $product->main_image) : null,
            ],
            'images' => $exists
                ? $product->images->map(fn ($img) => ['id' => $img->id, 'url' => asset('storage/' . $img->path)])->values()
                : [],
            'notes' => $exists ? $product->notes->map(fn ($n) => $this->presentNote($n))->values() : [],
            'statuses' => collect(Product::STATUSES)->map(fn ($l, $k) => ['value' => $k, 'label' => $l])->values(),
            'voivodeships' => Product::VOIVODESHIPS,
            'noteTypes' => collect(ParishNote::TYPES)->map(fn ($l, $k) => ['value' => $k, 'label' => $l])->values(),
            'urls' => [
                'store' => route('panel.products.store'),
                'update' => $exists ? route('panel.products.update', $product) : null,
                'index' => route('panel.products.index'),
                'editorUpload' => route('panel.products.editor-upload'),
                'status' => $exists ? route('panel.parishes.status', $product) : null,
                // Szablony URL-i (podmiana __ID__ po stronie React).
                'imageDelete' => $exists ? route('panel.products.images.delete', [$product, '__ID__']) : null,
                'notesStore' => $exists ? route('panel.parishes.notes.store', $product) : null,
                'notesDestroy' => $exists ? route('panel.parishes.notes.destroy', [$product, '__ID__']) : null,
            ],
        ]);
    }

    /** Serializacja notatki CRM dla React. */
    private function presentNote(ParishNote $n): array
    {
        return [
            'id' => $n->id,
            'body' => $n->body,
            'type' => $n->type,
            'type_label' => $n->typeLabel(),
            'author' => $n->author,
            'created_at' => $n->created_at?->format('Y-m-d H:i'),
        ];
    }

    /**
     * Szybka zmiana statusu parafii (kontakt → test → wdrożenie → aktywna).
     * Status 'aktywna' publikuje parafię (active=true), pozostałe ją ukrywają.
     */
    public function status(Request $request, Product $product)
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', array_keys(Product::STATUSES))],
        ]);

        $product->update([
            'status' => $data['status'],
            'active' => $data['status'] === 'aktywna',
        ]);

        return back()->with('success', 'Status parafii zmieniony na: ' . $product->statusLabel() . '.');
    }

    /**
     * Dodanie notatki CRM (AJAX) — zwraca utworzoną notatkę jako JSON.
     */
    public function storeNote(Request $request, Product $product)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'type' => ['required', 'string', 'in:' . implode(',', array_keys(ParishNote::TYPES))],
        ], [], ['body' => 'treść', 'type' => 'typ']);

        $note = $product->notes()->create([
            'body' => $data['body'],
            'type' => $data['type'],
            'author' => optional($request->user())->name ?? optional($request->user())->email,
            // created_at ma DB default useCurrent(), ale ustawiamy jawnie,
            // by od razu zwrócić datę w odpowiedzi JSON (bez refetcha).
            'created_at' => now(),
        ]);

        return response()->json([
            'id' => $note->id,
            'body' => $note->body,
            'type' => $note->type,
            'type_label' => $note->typeLabel(),
            'author' => $note->author,
            'created_at' => $note->created_at?->format('Y-m-d H:i'),
        ], 201);
    }

    /**
     * Usunięcie notatki CRM (AJAX).
     */
    public function destroyNote(Product $product, ParishNote $note)
    {
        abort_unless($note->product_id === $product->id, 404);

        $note->delete();

        return response()->json(['deleted' => true]);
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validated($request, $product);

        if ($main = $this->storeMainImage($request)) {
            $data['main_image'] = $main;
        }

        $product->update($data);
        $this->storeGallery($request, $product);

        return redirect()->route('panel.products.index')->with('success', 'Produkt zapisany.');
    }

    public function toggle(Product $product)
    {
        $product->update(['active' => ! $product->active]);

        return back()->with('success', $product->active ? 'Produkt aktywowany.' : 'Produkt dezaktywowany.');
    }

    public function deleteImage(Product $product, int $imageId)
    {
        $product->images()->where('id', $imageId)->delete();

        return back()->with('success', 'Zdjęcie usunięte.');
    }

    public function stats(Product $product, ShopStatsService $stats)
    {
        $total = $stats->summary(productId: $product->id);
        $last30 = $stats->summary(productId: $product->id, days: 30);
        $series = $stats->dailyPurchases(productId: $product->id, days: 30);

        // Przychód formatujemy tu (grosze → PLN), reszta metryk to liczby całkowite.
        $withRevenue = fn (array $s) => [...$s, 'revenue' => ShopStatsService::formatPln($s['revenue'])];

        return Inertia::render('Panel/Products/Stats', [
            'product' => ['name' => $product->name],
            'total' => $withRevenue($total),
            'last30' => $withRevenue($last30),
            // Lejek sprzedażowy (łącznie) — wartości bezwzględne, skalowanie w React.
            'funnel' => [
                ['label' => 'Otwarcia tagów', 'value' => $total['opens']],
                ['label' => 'Kliki „Kup"', 'value' => $total['clicks']],
                ['label' => 'Zakupy', 'value' => $total['purchases']],
            ],
            'series' => $series,
            'indexUrl' => route('panel.products.index'),
        ]);
    }

    /**
     * Upload zdjęcia z edytora WYSIWYG — zwraca URL do wstawienia w treść.
     */
    public function uploadEditorImage(Request $request)
    {
        $request->validate(['image' => ['required', 'image', 'max:8192']]);

        $path = $request->file('image')->store('products/editor', 'public');

        return response()->json(['url' => asset('storage/' . $path)]);
    }

    private function validated(Request $request, ?Product $product = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'regex:/^\d{1,5}([.,]\d{1,2})?$/'],
            'tag_uid' => ['required', 'string', 'max:255', Rule::unique('products', 'tag_uid')->ignore($product?->id)],
            'pickup_instruction' => ['nullable', 'string', 'max:2000'],
            'description_html' => ['nullable', 'string'],
            // Pola CRM:
            'phone' => ['nullable', 'string', 'max:255'],
            'website' => ['nullable', 'string', 'max:255'],
            'voivodeship' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'string', 'in:' . implode(',', array_keys(Product::STATUSES))],
        ], [], [
            'name' => 'nazwa', 'purpose' => 'cel zbiórki', 'price' => 'cena', 'tag_uid' => 'UID taga',
            'pickup_instruction' => 'instrukcja odbioru', 'description_html' => 'opis',
            'phone' => 'telefon', 'website' => 'strona www', 'voivodeship' => 'województwo',
            'status' => 'status',
        ]);

        // Cena wpisywana w złotówkach — w bazie trzymamy grosze.
        $data['price'] = (int) round(((float) str_replace(',', '.', (string) $data['price'])) * 100);
        // Status steruje publikacją: aktywna => publiczna, pozostałe => lead (ukryta).
        $data['active'] = $data['status'] === 'aktywna';

        return $data;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i = 2;
        while (Product::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }

    private function storeMainImage(Request $request): ?string
    {
        if (! $request->hasFile('main_image')) {
            return null;
        }

        $request->validate(['main_image' => ['image', 'max:8192']]);

        return $request->file('main_image')->store('products', 'public');
    }

    private function storeGallery(Request $request, Product $product): void
    {
        if (! $request->hasFile('gallery')) {
            return;
        }

        $request->validate(['gallery.*' => ['image', 'max:8192']]);

        $sort = (int) $product->images()->max('sort') + 1;
        foreach ($request->file('gallery') as $file) {
            $product->images()->create([
                'path' => $file->store('products', 'public'),
                'sort' => $sort++,
            ]);
        }
    }
}
