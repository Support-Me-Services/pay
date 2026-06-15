<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\Product;
use App\Modules\Storefront\Services\ShopStatsService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{
    public function index(ShopStatsService $stats)
    {
        $products = Product::with('images')->orderBy('id')->get()->map(fn (Product $product) => [
            'product' => $product,
            'stats' => $stats->summary(productId: $product->id),
        ]);

        return view('panel.products.index', compact('products'));
    }

    public function create()
    {
        return view('panel.products.form', ['product' => new Product()]);
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
        return view('panel.products.form', compact('product'));
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

        return view('panel.products.stats', compact('product', 'total', 'last30', 'series'));
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
            'price' => ['required', 'regex:/^\d{1,5}([.,]\d{1,2})?$/'],
            'tag_uid' => ['required', 'string', 'max:255', Rule::unique('products', 'tag_uid')->ignore($product?->id)],
            'pickup_instruction' => ['nullable', 'string', 'max:2000'],
            'description_html' => ['nullable', 'string'],
            'active' => ['nullable', 'boolean'],
        ], [], [
            'name' => 'nazwa', 'price' => 'cena', 'tag_uid' => 'UID taga',
            'pickup_instruction' => 'instrukcja odbioru', 'description_html' => 'opis',
        ]);

        // Cena wpisywana w złotówkach — w bazie trzymamy grosze.
        $data['price'] = (int) round(((float) str_replace(',', '.', (string) $data['price'])) * 100);
        $data['active'] = $request->boolean('active');

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
