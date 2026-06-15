<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\Salesperson;
use Illuminate\Http\Request;

class SalespersonController extends Controller
{
    public function index()
    {
        $salespeople = Salesperson::withCount('parishes')->orderBy('name')->get();

        return view('panel.salespeople.index', compact('salespeople'));
    }

    public function create()
    {
        return view('panel.salespeople.form', ['salesperson' => new Salesperson()]);
    }

    public function store(Request $request)
    {
        Salesperson::create($this->validated($request));

        return redirect()->route('panel.salespeople.index')->with('success', 'Handlowiec dodany.');
    }

    public function edit(Salesperson $salesperson)
    {
        $salesperson->load('parishes');

        return view('panel.salespeople.form', compact('salesperson'));
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
