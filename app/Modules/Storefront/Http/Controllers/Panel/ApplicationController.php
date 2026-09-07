<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use App\Modules\Storefront\Models\Organization;
use App\Services\OrgSvcClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

/**
 * Panel: zgłoszenia rekrutacyjne (sekcje „Aplikacje” / „Baza kandydatów") — per‑organizacja.
 * Faza 4 migracji: dane w org-svc — JobApplication już nie ma lokalnej tabeli.
 * CV zostaje na dysku Laravela (GCS) — org-svc trzyma tylko ścieżkę.
 */
class ApplicationController extends Controller
{
    public const STATUSES = ['pending' => 'Do sprawdzenia', 'accepted' => 'Zaakceptowany', 'rejected' => 'Odrzucony'];
    public const FUTURE_CONSENT_MONTHS = 24;

    private Organization $org;

    public function __construct(Request $request)
    {
        $org = $request->user()->activeOrganization($request);
        abort_unless($org && $org->canSee('applications'), 403);
        $this->org = $org;
    }

    /**
     * Skrzynka zgłoszeń rekrutacyjnych — najnowsze na górze.
     * Opcjonalny filtr po ofercie: ?position=ID.
     */
    public function index(Request $request)
    {
        $positionId = $request->integer('position') ?: null;
        $status = in_array($request->query('status'), array_keys(self::STATUSES), true)
            ? $request->query('status') : null;

        $client = app(OrgSvcClient::class);
        $applications = $client->listJobApplications($this->org->id, $positionId, $status);
        $allForOrg = $client->listJobApplications($this->org->id);
        $positionsById = collect($client->listJobPositions($this->org->id))->keyBy('id');

        $unread = count(array_filter($allForOrg, fn ($a) => ! $a['isRead']));
        $statusCounts = [];
        foreach ($allForOrg as $a) {
            if ($positionId && $a['jobPositionId'] !== $positionId) {
                continue;
            }
            $statusCounts[$a['status']] = ($statusCounts[$a['status']] ?? 0) + 1;
        }
        $filterPosition = $positionId ? $positionsById->get($positionId) : null;

        $base = $positionId ? ['position' => $positionId] : [];
        $total = array_sum($statusCounts);
        $tabs = [['label' => 'Wszystkie', 'count' => $total, 'url' => route('panel.applications.index', $base), 'active' => ! $status]];
        foreach (self::STATUSES as $key => $label) {
            $tabs[] = ['label' => $label, 'count' => (int) ($statusCounts[$key] ?? 0), 'url' => route('panel.applications.index', $base + ['status' => $key]), 'active' => $status === $key];
        }

        return Inertia::render('Panel/Applications/Index', [
            'items' => array_values(array_map(fn (array $a) => $this->present($a, $positionsById), $applications)),
            'unread' => $unread,
            'tabs' => $tabs,
            'filterPosition' => $filterPosition ? ['id' => $filterPosition['id'], 'title' => $filterPosition['title']] : null,
            'clearFilterUrl' => route('panel.applications.index'),
        ]);
    }

    /**
     * Serializacja zgłoszenia (odpowiedź org-svc) dla React (Inertia).
     * $positionsById — mapa id->stanowisko (org-svc), do tytułu oferty bez N+1.
     */
    private function present(array $a, ?\Illuminate\Support\Collection $positionsById = null): array
    {
        [$bg, $fg] = $this->statusColors($a['status']);
        $positionTitle = null;
        if ($a['jobPositionId']) {
            $positionTitle = $positionsById?->get($a['jobPositionId'])['title']
                ?? $this->tryGetPosition($a['jobPositionId'])['title'] ?? null;
        }

        $consentAt = $a['futureRecruitmentConsentAt'] ? \Carbon\Carbon::parse($a['futureRecruitmentConsentAt']) : null;

        return [
            'id' => $a['id'],
            'name' => $a['name'],
            'email' => $a['email'],
            'phone' => $a['phone'],
            'created_at' => \Carbon\Carbon::parse($a['createdAt'])->format('Y-m-d H:i'),
            'is_read' => (bool) $a['isRead'],
            'status' => $a['status'],
            'status_label' => self::STATUSES[$a['status']] ?? 'Do sprawdzenia',
            'status_bg' => $bg,
            'status_fg' => $fg,
            'position_title' => $positionTitle,
            'cv_url' => $a['cvPath'] ? route('panel.applications.cv', $a['id']) : null,
            'cv_name' => $a['cvOriginalName'],
            'show_url' => route('panel.applications.show', $a['id']),
            'status_url' => route('panel.applications.status', $a['id']),
            'destroy_url' => route('panel.applications.destroy', $a['id']),
            // Zgoda na przyszłe rekrutacje (24 mies. od dnia udzielenia) — "active" liczone w org-svc.
            'future_consent' => (bool) $a['futureRecruitmentConsent'],
            'future_consent_at' => $consentAt?->format('Y-m-d'),
            'future_consent_until' => $consentAt?->copy()->addMonths(self::FUTURE_CONSENT_MONTHS)->format('Y-m-d'),
            'future_consent_active' => (bool) $a['futureConsentActive'],
        ];
    }

    private function statusColors(string $status): array
    {
        return match ($status) {
            'accepted' => ['#dcfce7', '#166534'],
            'rejected' => ['#fee2e2', '#991b1b'],
            default => ['#fef3c7', '#92400e'],
        };
    }

    /**
     * Baza kandydatów z AKTYWNĄ zgodą na przyszłe procesy rekrutacyjne.
     * Pokazujemy tylko zgody wciąż ważne (w okresie 24 miesięcy), najświeższe
     * u góry — wraz z danymi kontaktowymi i CV (jak w skrzynce „Aplikacje").
     */
    public function consents()
    {
        $items = app(OrgSvcClient::class)->listJobApplications($this->org->id, null, null, activeFutureConsent: true);
        usort($items, fn ($a, $b) => strcmp($b['futureRecruitmentConsentAt'] ?? '', $a['futureRecruitmentConsentAt'] ?? ''));

        return Inertia::render('Panel/Applications/Consents', [
            'items' => array_values(array_map(fn (array $a) => $this->present($a), $items)),
            'consentMonths' => self::FUTURE_CONSENT_MONTHS,
            'indexUrl' => route('panel.applications.index'),
        ]);
    }

    /**
     * Zmiana statusu rekrutacyjnego (do sprawdzenia / zaakceptowany / odrzucony).
     */
    public function updateStatus(Request $request, int $application)
    {
        $data = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', array_keys(self::STATUSES))],
        ]);

        $result = app(OrgSvcClient::class)->updateJobApplicationStatus($application, $this->org->id, $data['status']);

        return back()->with('success', 'Status zmieniony na: ' . (self::STATUSES[$result['status']] ?? $result['status']) . '.');
    }

    /**
     * Szczegóły zgłoszenia — otwarcie oznacza je jako przeczytane.
     */
    public function show(int $application)
    {
        $item = $this->ownedApplicationOrAbort($application);
        if (! $item['isRead']) {
            $item = app(OrgSvcClient::class)->markJobApplicationRead($application, $this->org->id);
        }

        return Inertia::render('Panel/Applications/Show', [
            'application' => $this->present($item) + ['message' => $item['message']],
            'statusOptions' => collect(self::STATUSES)->map(fn ($label, $value) => ['value' => $value, 'label' => $label])->values(),
            'indexUrl' => route('panel.applications.index'),
        ]);
    }

    /**
     * Pobranie pliku CV — wyłącznie dla zalogowanego administratora.
     * Plik leży na prywatnym dysku, więc nie jest dostępny publicznie.
     */
    public function cv(int $application)
    {
        $item = $this->ownedApplicationOrAbort($application);
        abort_unless($item['cvPath'] && Storage::disk('local')->exists($item['cvPath']), 404);

        return Storage::disk('local')->download(
            $item['cvPath'],
            $item['cvOriginalName'] ?: basename($item['cvPath'])
        );
    }

    /**
     * Usunięcie zgłoszenia wraz z plikiem CV.
     */
    public function destroy(int $application)
    {
        $this->ownedApplicationOrAbort($application);
        $result = app(OrgSvcClient::class)->deleteJobApplication($application, $this->org->id);
        if (! empty($result['deletedCvPath'])) {
            Storage::disk('local')->delete($result['deletedCvPath']);
        }

        return redirect()->route('panel.applications.index')->with('success', 'Zgłoszenie usunięte.');
    }

    /** Ładuje zgłoszenie i weryfikuje, że należy do aktywnej organizacji. */
    private function ownedApplicationOrAbort(int $id): array
    {
        $item = app(OrgSvcClient::class)->getJobApplication($id);
        abort_unless((int) $item['organizationId'] === $this->org->id, 403);

        return $item;
    }

    private function tryGetPosition(int $id): ?array
    {
        try {
            return app(OrgSvcClient::class)->getJobPosition($id);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
