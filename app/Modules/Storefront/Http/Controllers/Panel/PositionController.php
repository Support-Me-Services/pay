<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\Organization;
use App\Services\OrgSvcClient;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Panel: oferty pracy (sekcja „Praca") — per‑organizacja (aktywna organizacja usera).
 * Faza 4 migracji: dane i CRUD w org-svc — JobPosition już nie ma lokalnej tabeli.
 */
class PositionController extends Controller
{
    private Organization $org;

    public function __construct(Request $request)
    {
        $org = $request->user()->activeOrganization($request);
        abort_unless($org && $org->canSee('positions'), 403);
        $this->org = $org;
    }

    public function index()
    {
        $positions = app(OrgSvcClient::class)->listJobPositions($this->org->id);
        usort($positions, fn ($a, $b) => $a['sort'] <=> $b['sort']);

        return Inertia::render('Panel/Positions/Index', [
            'items' => array_values(array_map(fn (array $p) => $this->present($p), $positions)),
            'createUrl' => route('panel.positions.create'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Panel/Positions/Form', [
            'item' => null,
            'storeUrl' => route('panel.positions.store'),
            'indexUrl' => route('panel.positions.index'),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['organizationId'] = $this->org->id;

        app(OrgSvcClient::class)->createJobPosition($data);

        return redirect()->route('panel.positions.index')->with('success', 'Stanowisko dodane.');
    }

    public function edit(int $position)
    {
        $item = $this->ownedPositionOrAbort($position);

        return Inertia::render('Panel/Positions/Form', [
            'item' => $this->present($item),
            'storeUrl' => route('panel.positions.store'),
            'indexUrl' => route('panel.positions.index'),
        ]);
    }

    /** Serializacja stanowiska (odpowiedź org-svc) dla React (Inertia). */
    private function present(array $p): array
    {
        return [
            'id' => $p['id'],
            'title' => $p['title'],
            'location' => $p['location'],
            'employment_type' => $p['employmentType'],
            'description_html' => $p['descriptionHtml'],
            'short_description' => $p['shortDescription'],
            'sort' => (int) $p['sort'],
            'active' => (bool) $p['active'],
            'applications_count' => (int) $p['applicationsCount'],
            'applications_url' => route('panel.applications.index', ['position' => $p['id']]),
            'edit_url' => route('panel.positions.edit', $p['id']),
            'update_url' => route('panel.positions.update', $p['id']),
            'toggle_url' => route('panel.positions.toggle', $p['id']),
            'destroy_url' => route('panel.positions.destroy', $p['id']),
        ];
    }

    public function update(Request $request, int $position)
    {
        $this->ownedPositionOrAbort($position);
        $data = $this->validated($request);
        $data['organizationId'] = $this->org->id;

        app(OrgSvcClient::class)->updateJobPosition($position, $data);

        return redirect()->route('panel.positions.index')->with('success', 'Stanowisko zapisane.');
    }

    public function toggle(int $position)
    {
        $this->ownedPositionOrAbort($position);
        $result = app(OrgSvcClient::class)->toggleJobPosition($position, $this->org->id);

        return back()->with('success', $result['active'] ? 'Stanowisko aktywowane.' : 'Stanowisko dezaktywowane.');
    }

    public function destroy(int $position)
    {
        $this->ownedPositionOrAbort($position);
        app(OrgSvcClient::class)->deleteJobPosition($position, $this->org->id);

        return redirect()->route('panel.positions.index')->with('success', 'Stanowisko usunięte.');
    }

    /** Ładuje stanowisko i weryfikuje, że należy do aktywnej organizacji. */
    private function ownedPositionOrAbort(int $id): array
    {
        $item = app(OrgSvcClient::class)->getJobPosition($id);
        abort_unless((int) $item['organizationId'] === $this->org->id, 403);

        return $item;
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', 'string', 'max:255'],
            'description_html' => ['nullable', 'string'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'active' => ['nullable', 'boolean'],
        ], [], [
            'title' => 'tytuł',
            'location' => 'lokalizacja',
            'employment_type' => 'rodzaj zatrudnienia',
            'description_html' => 'opis',
            'short_description' => 'krótki opis',
            'sort' => 'kolejność',
        ]);

        return [
            'title' => $data['title'],
            'location' => $data['location'] ?? null,
            'employmentType' => $data['employment_type'] ?? null,
            'descriptionHtml' => $data['description_html'] ?? null,
            'shortDescription' => $data['short_description'] ?? null,
            'sort' => (int) ($data['sort'] ?? 0),
            'active' => $request->boolean('active'),
        ];
    }
}
