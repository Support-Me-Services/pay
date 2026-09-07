<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Mail\JobApplicationReceived;
use App\Modules\Storefront\Models\Organization;
use App\Services\OrgSvcClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * „Praca" globalna (/praca) — oferty właściciela głównego (root owner).
 * Odpowiednik per-konto: UserCareersController pod /people/{handle}/praca.
 *
 * Faza 4 migracji: JobPosition/JobApplication żyją w org-svc — brak route
 * model bindingu (parametry to teraz zwykłe int ID), CV zostaje na dysku
 * Laravela (GCS), rate-limiting/honeypot formularza zostają tu (warstwa brzegowa).
 */
class CareersController extends Controller
{
    /** Hashowany URL wspólnego arkusza podstron (cache-bust). */
    private function subpagesCss(): string
    {
        return asset('css/subpages.css') . '?v=' . substr(md5_file(public_path('css/subpages.css')), 0, 10);
    }

    /** Czy oferta jest zdalna/hybrydowa (model nie ma pola — wykrywamy z tekstu). */
    private function isRemote(array $p): bool
    {
        $haystack = mb_strtolower(trim(($p['location'] ?? '') . ' ' . ($p['employmentType'] ?? '')));

        return $haystack !== '' && Str::contains($haystack, ['zdaln', 'remote', 'hybryd']);
    }

    /**
     * GET /praca — lista aktywnych stanowisk pracy.
     */
    public function index()
    {
        $owner = Organization::rootOrganization();
        $positions = $owner ? app(OrgSvcClient::class)->listJobPositions($owner->id, activeOnly: true) : [];
        usort($positions, fn ($a, $b) => $a['sort'] <=> $b['sort']);

        $fallback = 'Dołącz do zespołu, który tworzy płatności NFC dla dobra wspólnego — technologię wspierającą parafie, fundacje i lokalne inicjatywy. Szukamy osób, które chcą łączyć nowoczesne rozwiązania z realnym wpływem na ludzi.';

        return Inertia::render('Storefront/Praca', [
            'positions' => array_values(array_map(function (array $p) use ($fallback) {
                $short = trim((string) $p['shortDescription']);

                return [
                    'id' => $p['id'],
                    'title' => $p['title'],
                    'employment_type' => $p['employmentType'],
                    'location' => $p['location'],
                    'is_remote' => $this->isRemote($p),
                    'excerpt' => $short !== '' ? $short : $fallback,
                    'show_url' => route('careers.show', $p['id']),
                ];
            }, $positions)),
            'css' => $this->subpagesCss(),
            'pageTitle' => 'Praca — ' . config('shop.name'),
            'pageDescription' => 'Dołącz do zespołu — aktualne oferty pracy i wolontariatu.',
        ]);
    }

    /**
     * GET /praca/oferta/{position} — pojedyncza oferta pracy na osobnej podstronie.
     */
    public function show(int $position)
    {
        $owner = Organization::rootOrganization();
        $item = $this->tryGetPosition($position);
        abort_unless($item && $item['active'] && $owner && $item['organizationId'] === $owner->id, 404);

        $others = collect(app(OrgSvcClient::class)->listJobPositions($owner->id, activeOnly: true))
            ->reject(fn ($o) => $o['id'] === $item['id'])
            ->sortBy('sort')->take(3);

        $plain = trim(strip_tags($item['descriptionHtml'] ?? ''));

        return Inertia::render('Storefront/Oferta', [
            'position' => [
                'title' => $item['title'],
                'employment_type' => trim((string) $item['employmentType']),
                'location' => trim((string) $item['location']),
                'is_remote' => $this->isRemote($item),
                'description_html' => $plain !== '' ? $item['descriptionHtml'] : null,
                'apply_url' => route('careers.apply', $item['id']),
            ],
            'others' => $others->map(fn (array $o) => [
                'title' => $o['title'],
                'meta' => collect([$o['employmentType'], $o['location']])->filter()->implode(' · ') ?: 'Zobacz szczegóły',
                'show_url' => route('careers.show', $o['id']),
            ])->values(),
            'careersUrl' => route('careers'),
            'css' => $this->subpagesCss(),
            'pageTitle' => $item['title'] . ' — Praca — SupportME',
            'pageDescription' => Str::limit($plain, 150) ?: 'Dołącz do zespołu SupportME — technologia, która pomaga czynić dobro.',
        ]);
    }

    /**
     * GET /praca/aplikuj — formularz aplikacji spontanicznej (bez oferty).
     * GET /praca/{position}/aplikuj — formularz aplikacji na konkretną ofertę.
     */
    public function applyForm(?int $position = null)
    {
        $item = $position ? $this->tryGetPosition($position) : null;
        if ($position) {
            $owner = Organization::rootOrganization();
            abort_unless($item && $owner && $item['organizationId'] === $owner->id, 404);
        }

        return Inertia::render('Storefront/Aplikuj', [
            'position' => $item ? ['title' => $item['title']] : null,
            'storeUrl' => $item ? route('careers.apply.store', $position) : route('careers.apply.general.store'),
            'careersUrl' => route('careers'),
            'css' => $this->subpagesCss(),
            'pageTitle' => ($item ? 'Aplikuj: ' . $item['title'] : 'Aplikacja spontaniczna') . ' — ' . config('shop.name'),
            'pageDescription' => 'Wyślij swoje zgłoszenie rekrutacyjne wraz z CV.',
        ]);
    }

    /**
     * POST /praca/aplikuj oraz POST /praca/{position}/aplikuj — zapis zgłoszenia.
     * CV przechowywane jest na PRYWATNYM dysku (storage/app/private/cv).
     */
    public function applyStore(Request $request, ?int $position = null)
    {
        $item = $position ? $this->tryGetPosition($position) : null;

        // Honeypot antyspamowy — boty wypełniają ukryte pole "website".
        // Udajemy sukces (bez zapisu pliku/wpisu/maila), by nie zdradzać mechanizmu.
        if ($request->filled('website')) {
            return ($item ? redirect()->route('careers.apply', $position) : redirect()->route('careers.apply.general'))
                ->with('success', 'Dziękujemy za zgłoszenie — odezwiemy się.')
                ->with('apply_done', true);
        }

        // Walidacja serwerowa (nie polegamy tylko na atrybutach HTML):
        //  - CV WYMAGANE, wyłącznie PDF/DOC/DOCX (rozszerzenie + MIME), max 5 MB,
        //  - zgoda RODO musi być zaznaczona (accepted),
        //  - ochrona przed niedozwolonymi plikami: mimes + mimetypes.
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'message' => ['nullable', 'string', 'max:5000'],
            'cv' => [
                'required', 'file', 'max:5120',
                'mimes:pdf,doc,docx',
                'mimetypes:application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ],
            'rodo' => ['accepted'],
            // Nieobowiązkowa zgoda na przyszłe rekrutacje (checkbox — 0/1).
            'future_consent' => ['nullable', 'boolean'],
        ], [
            'cv.required' => 'Załącz plik CV (PDF, DOC lub DOCX).',
            'cv.mimes' => 'Dozwolone formaty CV to PDF, DOC i DOCX.',
            'cv.mimetypes' => 'Dozwolone formaty CV to PDF, DOC i DOCX.',
            'cv.max' => 'Plik CV może mieć maksymalnie 5 MB.',
            'rodo.accepted' => 'Wymagana jest zgoda na przetwarzanie danych (RODO).',
        ], [
            'name' => 'imię i nazwisko',
            'email' => 'e-mail',
            'phone' => 'telefon',
            'message' => 'list motywacyjny',
            'cv' => 'plik CV',
        ]);

        $file = $request->file('cv');
        // Dysk 'local' (storage/app/private) — pliki NIE są publicznie dostępne.
        $cvPath = Storage::disk('local')->putFile('cv', $file);
        $cvOriginalName = $file->getClientOriginalName();

        // Nieobowiązkowa zgoda na przyszłe rekrutacje — zapisujemy fakt zgody
        // wraz z datą jej udzielenia (org-svc stempluje "teraz" po swojej stronie).
        $futureConsent = $request->boolean('future_consent');

        $owner = $item ? Organization::find($item['organizationId']) : Organization::rootOrganization();

        app(OrgSvcClient::class)->createJobApplication([
            'organizationId' => $owner?->id,
            'jobPositionId' => $item ? $item['id'] : null,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'] ?? null,
            'cvPath' => $cvPath,
            'cvOriginalName' => $cvOriginalName,
            'futureRecruitmentConsent' => $futureConsent,
        ]);

        // Wyślij zgłoszenie z CV w załączniku na skonfigurowany adres rekrutacji.
        // Wysyłamy tylko gdy skonfigurowany jest PRAWDZIWY mailer (nie 'log'/'array') —
        // dzięki temu w trybie „tylko panel" nie zaśmiecamy logów załącznikami CV.
        // Po ustawieniu MAIL_MAILER=smtp (+ dane SMTP) maile zaczną wychodzić automatycznie.
        // Błąd wysyłki NIE blokuje zgłoszenia — jest już zapisane w bazie i panelu.
        if (! in_array(config('mail.default'), ['log', 'array', null], true)) {
            try {
                Mail::to(config('shop.careers_email'))->send(new JobApplicationReceived(
                data: [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'message' => $data['message'] ?? null,
                    'position' => $item['title'] ?? null,
                ],
                cvAbsolutePath: Storage::disk('local')->path($cvPath),
                cvOriginalName: $cvOriginalName,
            ));
            } catch (\Throwable $e) {
                Log::error('Rekrutacja: nie udało się wysłać maila ze zgłoszeniem', [
                    'error' => $e->getMessage(),
                    'to' => config('shop.careers_email'),
                ]);
            }
        }

        // Powrót na stronę formularza aplikacji z potwierdzeniem (aplikuj.blade
        // renderuje session('success')). Aplikacja jest składana na osobnej
        // podstronie /praca/{position}/aplikuj — tam pokazujemy „Dziękujemy".
        $redirect = $item ? redirect()->route('careers.apply', $position) : redirect()->route('careers.apply.general');

        return $redirect
            ->with('success', 'Dziękujemy za zgłoszenie — odezwiemy się.')
            ->with('apply_done', true);
    }

    /** org-svc zwraca 404 dla nieistniejącego stanowiska. */
    private function tryGetPosition(int $id): ?array
    {
        try {
            return app(OrgSvcClient::class)->getJobPosition($id);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
