<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\BeneficiaryNode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Panel: edytor podstrony „Wspieramy" — węzły (nagłówek + grafika + tekst).
 * Kolejność ustawiana przeciąganiem (reorder). Grafika i treść (Quill) jak
 * w edytorze produktów.
 */
class BeneficiaryNodeController extends Controller
{
    public function index()
    {
        $nodes = BeneficiaryNode::ordered()->get();

        return view('panel.beneficiaries.index', compact('nodes'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['image'] = $this->storeImage($request);
        $data['position'] = (int) BeneficiaryNode::max('position') + 1;

        BeneficiaryNode::create($data);

        return redirect()->route('panel.beneficiaries.index')->with('success', 'Węzeł dodany.');
    }

    public function update(Request $request, BeneficiaryNode $node)
    {
        $data = $this->validated($request);

        if ($img = $this->storeImage($request)) {
            if ($node->image) {
                Storage::disk('public')->delete($node->image);
            }
            $data['image'] = $img;
        } elseif ($request->boolean('remove_image')) {
            if ($node->image) {
                Storage::disk('public')->delete($node->image);
            }
            $data['image'] = null;
        }

        $node->update($data);

        return redirect()->route('panel.beneficiaries.index')->with('success', 'Węzeł zapisany.');
    }

    public function destroy(BeneficiaryNode $node)
    {
        if ($node->image) {
            Storage::disk('public')->delete($node->image);
        }
        $node->delete();

        return redirect()->route('panel.beneficiaries.index')->with('success', 'Węzeł usunięty.');
    }

    /** Zapis nowej kolejności (drag & drop) — AJAX. */
    public function reorder(Request $request)
    {
        $ids = (array) $request->input('order', []);
        foreach (array_values($ids) as $i => $id) {
            BeneficiaryNode::whereKey((int) $id)->update(['position' => $i]);
        }

        return response()->json(['ok' => true]);
    }

    /** Walidacja pól węzła (bez grafiki/pozycji — te obsłużone osobno). */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'heading' => ['required', 'string', 'max:255'],
            'image_side' => ['required', 'in:left,right'],
            'image_scale' => ['nullable', 'integer', 'min:20', 'max:400'],
            'image_x' => ['nullable', 'integer', 'min:-100', 'max:100'],
            'image_y' => ['nullable', 'integer', 'min:-100', 'max:100'],
            'text_align' => ['required', 'in:left,center,right'],
            'body_html' => ['nullable', 'string', 'max:20000'],
        ], [], [
            'heading' => 'nagłówek',
            'image_side' => 'położenie grafiki',
            'text_align' => 'wyrównanie tekstu',
            'body_html' => 'treść',
        ]);

        return [
            'heading' => $data['heading'],
            'image_side' => $data['image_side'],
            'image_scale' => (int) ($data['image_scale'] ?? 100),
            'image_x' => (int) ($data['image_x'] ?? 0),
            'image_y' => (int) ($data['image_y'] ?? 0),
            'text_align' => $data['text_align'],
            'body_html' => $data['body_html'] ?? null,
        ];
    }

    private function storeImage(Request $request): ?string
    {
        if (! $request->hasFile('image_file')) {
            return null;
        }

        return $request->file('image_file')->store('beneficiaries', 'public');
    }
}
