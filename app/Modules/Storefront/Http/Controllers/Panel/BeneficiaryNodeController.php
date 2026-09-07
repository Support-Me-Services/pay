<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\Organization;
use App\Services\OrgSvcClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * Panel: edytor podstrony „O nas" — węzły (nagłówek + grafika + tekst).
 * Kolejność ustawiana przeciąganiem (reorder). Grafika i treść (Quill) jak
 * w edytorze produktów. Sekcja per‑organizacja (aktywna organizacja usera).
 *
 * Faza 2 migracji: dane (i CRUD/reorder) w org-svc (przez api-gateway) —
 * BeneficiaryNode już nie ma lokalnej tabeli w Laravelu (w odróżnieniu od
 * Organization, nic lokalnie nie ma FK do beneficiary_nodes, więc pełny
 * cutover bez lustra). Sam upload/serwowanie obrazu (GCS) zostaje w Laravelu
 * — org-svc trzyma wyłącznie ścieżkę jako string.
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
        $nodes = app(OrgSvcClient::class)->listBeneficiaryNodes($this->org->id);
        usort($nodes, fn ($a, $b) => $a['position'] <=> $b['position']);

        return Inertia::render('Panel/Beneficiaries/Index', [
            'nodes' => array_values(array_map(fn (array $n) => $this->present($n), $nodes)),
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

    /** Serializacja węzła „O nas" (odpowiedź org-svc) dla React. */
    private function present(array $n): array
    {
        return [
            'id' => $n['id'],
            'heading' => $n['heading'],
            'image_side' => $n['imageSide'],
            'text_align' => $n['textAlign'],
            'image' => $n['image'] ? asset('storage/' . $n['image']) : null,
            'image_scale' => $n['imageScale'],
            'image_x' => $n['imageX'],
            'image_y' => $n['imageY'],
            'image_right' => $n['imageSide'] === 'right',
            'body_html' => $n['bodyHtml'] ?? '',
        ];
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['organizationId'] = $this->org->id;
        $data['image'] = $this->storeImage($request);

        app(OrgSvcClient::class)->createBeneficiaryNode($data);

        return redirect()->route('panel.beneficiaries.index')->with('success', 'Węzeł dodany.');
    }

    public function update(Request $request, int $node)
    {
        $data = $this->validated($request);
        $data['organizationId'] = $this->org->id;

        if ($img = $this->storeImage($request)) {
            $this->deleteOldImage($node);
            $data['image'] = $img;
        } elseif ($request->boolean('remove_image')) {
            $this->deleteOldImage($node);
            $data['image'] = null;
        }

        app(OrgSvcClient::class)->updateBeneficiaryNode($node, $data);

        return redirect()->route('panel.beneficiaries.index')->with('success', 'Węzeł zapisany.');
    }

    public function destroy(int $node)
    {
        $result = app(OrgSvcClient::class)->deleteBeneficiaryNode($node, $this->org->id);
        if (! empty($result['deletedImage'])) {
            Storage::disk('public')->delete($result['deletedImage']);
        }

        return redirect()->route('panel.beneficiaries.index')->with('success', 'Węzeł usunięty.');
    }

    /** Zapis nowej kolejności (drag & drop) — AJAX. */
    public function reorder(Request $request)
    {
        $ids = array_map('intval', (array) $request->input('order', []));
        app(OrgSvcClient::class)->reorderBeneficiaryNodes($this->org->id, $ids);

        return response()->json(['ok' => true]);
    }

    /** Usuwa poprzedni obrazek pliku (przed nadpisaniem/usunięciem) — trzeba znać go PRZED wywołaniem org-svc. */
    private function deleteOldImage(int $nodeId): void
    {
        $nodes = app(OrgSvcClient::class)->listBeneficiaryNodes($this->org->id);
        $existing = collect($nodes)->firstWhere('id', $nodeId);
        if ($existing && ! empty($existing['image'])) {
            Storage::disk('public')->delete($existing['image']);
        }
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
            'imageSide' => $data['image_side'],
            'imageScale' => (int) ($data['image_scale'] ?? 100),
            'imageX' => (int) ($data['image_x'] ?? 0),
            'imageY' => (int) ($data['image_y'] ?? 0),
            'textAlign' => $data['text_align'],
            'bodyHtml' => $data['body_html'] ?? null,
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
