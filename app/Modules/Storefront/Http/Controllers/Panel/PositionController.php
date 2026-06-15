<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\JobPosition;
use Illuminate\Http\Request;

class PositionController extends Controller
{
    public function index()
    {
        $positions = JobPosition::orderBy('sort')->orderBy('id')->get();

        return view('panel.positions.index', compact('positions'));
    }

    public function create()
    {
        return view('panel.positions.form', ['position' => new JobPosition()]);
    }

    public function store(Request $request)
    {
        JobPosition::create($this->validated($request));

        return redirect()->route('panel.positions.index')->with('success', 'Stanowisko dodane.');
    }

    public function edit(JobPosition $position)
    {
        return view('panel.positions.form', compact('position'));
    }

    public function update(Request $request, JobPosition $position)
    {
        $position->update($this->validated($request));

        return redirect()->route('panel.positions.index')->with('success', 'Stanowisko zapisane.');
    }

    public function toggle(JobPosition $position)
    {
        $position->update(['active' => ! $position->active]);

        return back()->with('success', $position->active ? 'Stanowisko aktywowane.' : 'Stanowisko dezaktywowane.');
    }

    public function destroy(JobPosition $position)
    {
        $position->delete();

        return redirect()->route('panel.positions.index')->with('success', 'Stanowisko usunięte.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'employment_type' => ['nullable', 'string', 'max:255'],
            'description_html' => ['nullable', 'string'],
            'sort' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'active' => ['nullable', 'boolean'],
        ], [], [
            'title' => 'tytuł',
            'location' => 'lokalizacja',
            'employment_type' => 'rodzaj zatrudnienia',
            'description_html' => 'opis',
            'sort' => 'kolejność',
        ]);

        $data['active'] = $request->boolean('active');
        $data['sort'] = (int) ($data['sort'] ?? 0);

        return $data;
    }
}
