<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\BeneficiaryNode;
use App\Modules\Storefront\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * Panel: edytor podstrony „O nas" — węzły (nagłówek + grafika + tekst).
 * Kolejność ustawiana przeciąganiem (reorder). Grafika i treść (Quill) jak
 * w edytorze produktów. Sekcja per‑organizacja (aktywna organizacja usera).
 */
class BeneficiaryNodeController extends Controller
{
    private Organization $org;

    public function __construct(Request $request)
    {
        $org = $request->user()->activeOrganization($request);
        abort_unless($org && $org->canSee('beneficiaries'), 403);
        $this->org = $org;
    }

    public function index()
    {
        $nodes = BeneficiaryNode::forOrganization($this->org->id)->ordered()->get();

        return Inertia::render('Panel/Beneficiaries/Index', [
            'nodes' => $nodes->map(fn (BeneficiaryNode $n) => $this->present($n))->values(),
            'urls' => [
                'store' => route('panel.beneficiaries.store'),
                'reorder' => route('panel.beneficiaries.reorder'),
                'editorUpload' => route('panel.editor-upload'),
                'public' => route('beneficiaries'),
                // Szablony (podmiana __ID__ po stronie React).
                'update' => route('panel.beneficiaries.update', '__ID__'),
                'destroy' => route('panel.beneficiaries.destroy', '__ID__'),
            ],
        ]);
    }

    /** Serializacja węzła „O nas" dla React. */
    private function present(BeneficiaryNode $n): array
    {
        return [
            'id' => $n->id,
            'heading' => $n->heading,
            'image_side' => $n->image_side,
            'text_align' => $n->text_align,
            'image' => $n->image ? asset('storage/' . $n->image) : null,
            'image_scale' => $n->image_scale,
            'image_x' => $n->image_x,
            'image_y' => $n->image_y,
            'image_right' => $n->imageRight(),
            'body_html' => $n->body_html ?? '',
        ];
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['organization_id'] = $this->org->id;
        $data['image'] = $this->storeImage($request);
        $data['position'] = (int) BeneficiaryNode::forOrganization($this->org->id)->max('position') + 1;

        BeneficiaryNode::create($data);

        return redirect()->route('panel.beneficiaries.index')->with('success', 'Węzeł dodany.');
    }

    public function update(Request $request, BeneficiaryNode $node)
    {
        $this->guard($node);
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
        $this->guard($node);
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
            BeneficiaryNode::forOrganization($this->org->id)->whereKey((int) $id)->update(['position' => $i]);
        }

        return response()->json(['ok' => true]);
    }

    /** Tylko aktywna organizacja może edytować/usuwać swój węzeł. */
    private function guard(BeneficiaryNode $node): void
    {
        abort_unless((int) $node->organization_id === $this->org->id, 403);
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

    /** Upload zdjęcia z edytora WYSIWYG — zwraca URL do wstawienia w treść. */
    public function uploadEditorImage(Request $request)
    {
        $request->validate(['image' => ['required', 'image', 'max:8192']]);

        $path = $request->file('image')->store('products/editor', 'public');

        return response()->json(['url' => asset('storage/' . $path)]);
    }
}
