<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\JobApplication;
use App\Modules\Storefront\Models\JobPosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ApplicationController extends Controller
{
    /**
     * Skrzynka zgłoszeń rekrutacyjnych — najnowsze na górze.
     * Opcjonalny filtr po ofercie: ?position=ID.
     */
    public function index(Request $request)
    {
        $positionId = $request->integer('position') ?: null;
        $status = in_array($request->query('status'), array_keys(JobApplication::STATUSES), true)
            ? $request->query('status') : null;

        $query = JobApplication::with('position')->orderByDesc('id');
        if ($positionId) {
            $query->where('job_position_id', $positionId);
        }
        if ($status) {
            $query->where('status', $status);
        }

        $applications = $query->get();
        $unread = JobApplication::where('is_read', false)->count();
        // Liczniki per status (z uwzględnieniem filtra oferty).
        $statusCounts = JobApplication::query()
            ->when($positionId, fn ($q) => $q->where('job_position_id', $positionId))
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
        $filterPosition = $positionId ? JobPosition::find($positionId) : null;

        return view('panel.applications.index', compact('applications', 'unread', 'filterPosition', 'status', 'statusCounts'));
    }

    /**
     * Zmiana statusu rekrutacyjnego (do sprawdzenia / zaakceptowany / odrzucony).
     */
    public function updateStatus(Request $request, JobApplication $application)
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', array_keys(JobApplication::STATUSES))],
        ]);

        $application->update(['status' => $data['status']]);

        return back()->with('success', 'Status zmieniony na: ' . $application->statusLabel() . '.');
    }

    /**
     * Szczegóły zgłoszenia — otwarcie oznacza je jako przeczytane.
     */
    public function show(JobApplication $application)
    {
        if (! $application->is_read) {
            $application->update(['is_read' => true]);
        }

        $application->load('position');

        return view('panel.applications.show', compact('application'));
    }

    /**
     * Pobranie pliku CV — wyłącznie dla zalogowanego administratora.
     * Plik leży na prywatnym dysku, więc nie jest dostępny publicznie.
     */
    public function cv(JobApplication $application)
    {
        abort_unless($application->cv_path && Storage::disk('local')->exists($application->cv_path), 404);

        return Storage::disk('local')->download(
            $application->cv_path,
            $application->cv_original_name ?: basename($application->cv_path)
        );
    }

    /**
     * Usunięcie zgłoszenia wraz z plikiem CV.
     */
    public function destroy(JobApplication $application)
    {
        if ($application->cv_path) {
            Storage::disk('local')->delete($application->cv_path);
        }

        $application->delete();

        return redirect()->route('panel.applications.index')->with('success', 'Zgłoszenie usunięte.');
    }
}
