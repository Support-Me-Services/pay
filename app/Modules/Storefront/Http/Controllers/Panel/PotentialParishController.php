<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\PotentialParish;
use App\Modules\Storefront\Models\Salesperson;
use Illuminate\Http\Request;

class PotentialParishController extends Controller
{
    /**
     * Lista potencjalnych parafii do obdzwonienia (CRM dla handlowców).
     * Filtry: województwo, miasto (search), nazwa (search), status, handlowiec.
     */
    public function index(Request $request)
    {
        // Walidacja/normalizacja filtrów — tylko dozwolone wartości słownikowe.
        $voivodeship = in_array($request->query('voivodeship'), Salesperson::VOIVODESHIPS, true)
            ? $request->query('voivodeship') : null;
        $status = in_array($request->query('status'), array_keys(PotentialParish::STATUSES), true)
            ? $request->query('status') : null;
        $salespersonId = $request->filled('salesperson_id') ? (int) $request->query('salesperson_id') : null;
        $name = trim((string) $request->query('name', ''));
        $city = trim((string) $request->query('city', ''));

        $query = PotentialParish::with('salesperson');

        if ($voivodeship) {
            $query->where('voivodeship', $voivodeship);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($salespersonId) {
            $query->where('salesperson_id', $salespersonId);
        }
        if ($name !== '') {
            $query->where('name', 'like', "%{$name}%");
        }
        if ($city !== '') {
            $query->where('city', 'like', "%{$city}%");
        }

        $parishes = $query->orderBy('voivodeship')->orderBy('city')->orderBy('name')
            ->paginate(50)
            ->withQueryString();

        // Liczniki globalne (niezależne od filtrów) — łączny i per status.
        $total = PotentialParish::count();
        $statusCounts = PotentialParish::query()
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');

        $salespeople = Salesperson::orderBy('name')->get();

        return view('panel.potential-parishes.index', compact(
            'parishes', 'total', 'statusCounts', 'salespeople',
            'voivodeship', 'status', 'salespersonId', 'name', 'city'
        ));
    }

    /**
     * Zmiana statusu leada + notatka + telefon + przypisanie handlowca.
     * Ustawia called_at przy pierwszym przejściu do statusu „zadzwoniono”.
     * Obsługuje auto-zapis AJAX (zwraca JSON) oraz klasyczny POST (redirect).
     */
    public function updateStatus(Request $request, PotentialParish $potentialParish)
    {
        $data = $request->validate([
            'status'         => ['required', 'string', 'in:' . implode(',', array_keys(PotentialParish::STATUSES))],
            'salesperson_id' => ['nullable', 'integer', 'exists:salespeople,id'],
            'note'           => ['nullable', 'string', 'max:5000'],
            'phone'          => ['nullable', 'string', 'max:50'],
        ], [], [
            'status' => 'status', 'salesperson_id' => 'handlowiec',
            'note' => 'notatka', 'phone' => 'telefon',
        ]);

        $potentialParish->status = $data['status'];
        $potentialParish->salesperson_id = $data['salesperson_id'] ?? null;
        $potentialParish->note = $data['note'] ?? null;
        $potentialParish->phone = isset($data['phone']) && trim($data['phone']) !== ''
            ? trim($data['phone']) : null;

        // Stempel pierwszego kontaktu — ustawiany raz, gdy lead przechodzi do „zadzwoniono”.
        if ($data['status'] === 'zadzwoniono' && $potentialParish->called_at === null) {
            $potentialParish->called_at = now();
        }

        $potentialParish->save();

        // Auto-zapis AJAX — zwracamy JSON bez przeładowania strony.
        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            $potentialParish->loadMissing('salesperson');

            return response()->json([
                'ok'            => true,
                'message'       => 'Zapisano',
                'status'        => $potentialParish->status,
                'status_label'  => $potentialParish->statusLabel(),
                'status_colors' => $potentialParish->statusColors(),
                'salesperson'   => $potentialParish->salesperson?->name,
                'called_at'     => $potentialParish->called_at?->format('d.m.Y'),
            ]);
        }

        return redirect()->back()->with('success', 'Status parafii zaktualizowany.');
    }
}
