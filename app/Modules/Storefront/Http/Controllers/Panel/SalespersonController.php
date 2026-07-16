<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\Salesperson;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SalespersonController extends Controller
{
    public function index()
    {
        $salespeople = Salesperson::withCount('parishes')->orderBy('name')->get();

        return Inertia::render('Panel/Salespeople/Index', [
            'items' => $salespeople->map(fn (Salesperson $sp) => $this->present($sp))->values(),
            'createUrl' => route('panel.salespeople.create'),
        ]);
    }

    public function create()
    {
        return Inertia::render('Panel/Salespeople/Form', [
            'item' => null,
            'parishes' => [],
            'voivodeshipOptions' => Salesperson::VOIVODESHIPS,
            'storeUrl' => route('panel.salespeople.store'),
            'indexUrl' => route('panel.salespeople.index'),
        ]);
    }

    public function store(Request $request)
    {
        Salesperson::create($this->validated($request));

        return redirect()->route('panel.salespeople.index')->with('success', 'Handlowiec dodany.');
    }

    public function edit(Salesperson $salesperson)
    {
        $salesperson->load('parishes');

        return Inertia::render('Panel/Salespeople/Form', [
            'item' => $this->present($salesperson),
            'parishes' => $salesperson->parishes->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'city' => $p->city,
                'edit_url' => route('panel.products.edit', $p),
            ])->values(),
            'voivodeshipOptions' => Salesperson::VOIVODESHIPS,
            'storeUrl' => route('panel.salespeople.store'),
            'indexUrl' => route('panel.salespeople.index'),
        ]);
    }

    /** Serializacja handlowca dla React (Inertia). */
    private function present(Salesperson $sp): array
    {
        return [
            'id' => $sp->id,
            'name' => $sp->name,
            'email' => $sp->email,
            'phone' => $sp->phone,
            'voivodeships' => $sp->voivodeships ?? [],
            'active' => (bool) $sp->active,
            'parishes_count' => (int) ($sp->parishes_count ?? 0),
            'parishes_url' => route('panel.products.index', ['q' => $sp->name]),
            'edit_url' => route('panel.salespeople.edit', $sp),
            'update_url' => route('panel.salespeople.update', $sp),
            'destroy_url' => route('panel.salespeople.destroy', $sp),
        ];
    }

    public function update(Request $request, Salesperson $salesperson)
    {
        $salesperson->update($this->validated($request));

        return redirect()->route('panel.salespeople.index')->with('success', 'Handlowiec zapisany.');
    }

    public function destroy(Salesperson $salesperson)
    {
        // FK nullOnDelete — parafie tracą przypisanie, ale nie znikają.
        $salesperson->delete();

        return redirect()->route('panel.salespeople.index')->with('success', 'Handlowiec usunięty.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'voivodeships' => ['nullable', 'array'],
            'voivodeships.*' => ['string', 'in:' . implode(',', Salesperson::VOIVODESHIPS)],
            'active' => ['nullable', 'boolean'],
        ], [], [
            'name' => 'imię i nazwisko', 'email' => 'e-mail', 'phone' => 'telefon',
            'voivodeships' => 'województwa',
        ]);

        $data['active'] = $request->boolean('active');
        // Pusta lista => null (kolumna nullable). Tablica => cast na JSON w modelu.
        $data['voivodeships'] = ! empty($data['voivodeships']) ? array_values($data['voivodeships']) : null;

        return $data;
    }
}
