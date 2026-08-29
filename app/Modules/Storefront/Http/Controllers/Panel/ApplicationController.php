<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\JobApplication;
use App\Modules\Storefront\Models\JobPosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/** Panel: zgłoszenia rekrutacyjne (sekcje „Aplikacje” / „Baza kandydatów") — per‑konto. */
class ApplicationController extends Controller
{
    public function __construct()
    {
        abort_unless(Auth::user()->canSee('applications'), 403);
    }

    /**
     * Skrzynka zgłoszeń rekrutacyjnych — najnowsze na górze.
     * Opcjonalny filtr po ofercie: ?position=ID.
     */
    public function index(Request $request)
    {
        $positionId = $request->integer('position') ?: null;
        $status = in_array($request->query('status'), array_keys(JobApplication::STATUSES), true)
            ? $request->query('status') : null;

        $query = JobApplication::forUser(Auth::id())->with('position')->orderByDesc('id');
        if ($positionId) {
            $query->where('job_position_id', $positionId);
        }
        if ($status) {
            $query->where('status', $status);
        }

        $applications = $query->get();
        $unread = JobApplication::forUser(Auth::id())->where('is_read', false)->count();
        // Liczniki per status (z uwzględnieniem filtra oferty).
        $statusCounts = JobApplication::forUser(Auth::id())
            ->when($positionId, fn ($q) => $q->where('job_position_id', $positionId))
            ->selectRaw('status, COUNT(*) as c')->groupBy('status')->pluck('c', 'status');
        $filterPosition = $positionId ? JobPosition::forUser(Auth::id())->find($positionId) : null;

        $base = $positionId ? ['position' => $positionId] : [];
        $total = (int) $statusCounts->sum();
        $tabs = [['label' => 'Wszystkie', 'count' => $total, 'url' => route('panel.applications.index', $base), 'active' => ! $status]];
        foreach (JobApplication::STATUSES as $key => $label) {
            $tabs[] = ['label' => $label, 'count' => (int) ($statusCounts[$key] ?? 0), 'url' => route('panel.applications.index', $base + ['status' => $key]), 'active' => $status === $key];
        }

        return Inertia::render('Panel/Applications/Index', [
            'items' => $applications->map(fn (JobApplication $a) => $this->present($a))->values(),
            'unread' => $unread,
            'tabs' => $tabs,
            'filterPosition' => $filterPosition ? ['id' => $filterPosition->id, 'title' => $filterPosition->title] : null,
            'clearFilterUrl' => route('panel.applications.index'),
        ]);
    }

    /** Serializacja zgłoszenia dla React (Inertia). */
    private function present(JobApplication $a): array
    {
        [$bg, $fg] = $a->statusColors();

        return [
            'id' => $a->id,
            'name' => $a->name,
            'email' => $a->email,
            'phone' => $a->phone,
            'created_at' => $a->created_at?->format('Y-m-d H:i'),
            'is_read' => (bool) $a->is_read,
            'status' => $a->status,
            'status_label' => $a->statusLabel(),
            'status_bg' => $bg,
            'status_fg' => $fg,
            'position_title' => $a->position?->title,
            'cv_url' => $a->cv_path ? route('panel.applications.cv', $a) : null,
            'cv_name' => $a->cv_original_name,
            'show_url' => route('panel.applications.show', $a),
            'status_url' => route('panel.applications.status', $a),
            'destroy_url' => route('panel.applications.destroy', $a),
            // Zgoda na przyszłe rekrutacje (24 mies. od dnia udzielenia).
            'future_consent' => (bool) $a->future_recruitment_consent,
            'future_consent_at' => $a->future_recruitment_consent_at?->format('Y-m-d'),
            'future_consent_until' => $a->futureConsentExpiresAt()?->format('Y-m-d'),
            'future_consent_active' => $a->futureConsentActive(),
        ];
    }

    /**
     * Baza kandydatów z AKTYWNĄ zgodą na przyszłe procesy rekrutacyjne.
     * Pokazujemy tylko zgody wciąż ważne (w okresie 24 miesięcy), najświeższe
     * u góry — wraz z danymi kontaktowymi i CV (jak w skrzynce „Aplikacje").
     */
    public function consents()
    {
        $items = JobApplication::forUser(Auth::id())->with('position')
            ->activeFutureConsent()
            ->orderByDesc('future_recruitment_consent_at')
            ->get();

        return Inertia::render('Panel/Applications/Consents', [
            'items' => $items->map(fn (JobApplication $a) => $this->present($a))->values(),
            'consentMonths' => JobApplication::FUTURE_CONSENT_MONTHS,
            'indexUrl' => route('panel.applications.index'),
        ]);
    }

    /**
     * Zmiana statusu rekrutacyjnego (do sprawdzenia / zaakceptowany / odrzucony).
     */
    public function updateStatus(Request $request, JobApplication $application)
    {
        $this->guard($application);
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
        $this->guard($application);
        if (! $application->is_read) {
            $application->update(['is_read' => true]);
        }

        $application->load('position');

        return Inertia::render('Panel/Applications/Show', [
            'application' => $this->present($application) + ['message' => $application->message],
            'statusOptions' => collect(JobApplication::STATUSES)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
            'indexUrl' => route('panel.applications.index'),
        ]);
    }

    /**
     * Pobranie pliku CV — wyłącznie dla zalogowanego administratora.
     * Plik leży na prywatnym dysku, więc nie jest dostępny publicznie.
     */
    public function cv(JobApplication $application)
    {
        $this->guard($application);
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
        $this->guard($application);
        if ($application->cv_path) {
            Storage::disk('local')->delete($application->cv_path);
        }

        $application->delete();

        return redirect()->route('panel.applications.index')->with('success', 'Zgłoszenie usunięte.');
    }

    /** Tylko właściciel może otwierać/zarządzać swoim zgłoszeniem. */
    private function guard(JobApplication $application): void
    {
        abort_unless((int) $application->user_id === (int) Auth::id(), 403);
    }
}
