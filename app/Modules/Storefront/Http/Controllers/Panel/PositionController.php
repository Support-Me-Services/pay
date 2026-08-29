<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\JobPosition;
use App\Modules\Storefront\Models\Organization;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Panel: oferty pracy (sekcja „Praca") — per‑organizacja (aktywna organizacja usera). */
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
        $positions = JobPosition::forOrganization($this->org->id)->withCount('applications')
            ->orderBy('sort')->orderBy('id')->get();

        return Inertia::render('Panel/Positions/Index', [
            'items' => $positions->map(fn (JobPosition $p) => $this->present($p))->values(),
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
        JobPosition::create($this->validated($request) + ['organization_id' => $this->org->id]);

        return redirect()->route('panel.positions.index')->with('success', 'Stanowisko dodane.');
    }

    public function edit(JobPosition $position)
    {
        $this->guard($position);

        return Inertia::render('Panel/Positions/Form', [
            'item' => $this->present($position),
            'storeUrl' => route('panel.positions.store'),
            'indexUrl' => route('panel.positions.index'),
        ]);
    }

    /** Serializacja stanowiska dla React (Inertia). */
    private function present(JobPosition $p): array
    {
        return [
            'id' => $p->id,
            'title' => $p->title,
            'location' => $p->location,
            'employment_type' => $p->employment_type,
            'description_html' => $p->description_html,
            'short_description' => $p->short_description,
            'sort' => (int) $p->sort,
            'active' => (bool) $p->active,
            'applications_count' => (int) ($p->applications_count ?? 0),
            'applications_url' => route('panel.applications.index', ['position' => $p->id]),
            'edit_url' => route('panel.positions.edit', $p),
            'update_url' => route('panel.positions.update', $p),
            'toggle_url' => route('panel.positions.toggle', $p),
            'destroy_url' => route('panel.positions.destroy', $p),
        ];
    }

    public function update(Request $request, JobPosition $position)
    {
        $this->guard($position);
        $position->update($this->validated($request));

        return redirect()->route('panel.positions.index')->with('success', 'Stanowisko zapisane.');
    }

    public function toggle(JobPosition $position)
    {
        $this->guard($position);
        $position->update(['active' => ! $position->active]);

        return back()->with('success', $position->active ? 'Stanowisko aktywowane.' : 'Stanowisko dezaktywowane.');
    }

    public function destroy(JobPosition $position)
    {
        $this->guard($position);
        $position->delete();

        return redirect()->route('panel.positions.index')->with('success', 'Stanowisko usunięte.');
    }

    /** Tylko aktywna organizacja może edytować/usuwać swoją ofertę. */
    private function guard(JobPosition $position): void
    {
        abort_unless((int) $position->organization_id === $this->org->id, 403);
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

        $data['active'] = $request->boolean('active');
        $data['sort'] = (int) ($data['sort'] ?? 0);

        return $data;
    }
}
