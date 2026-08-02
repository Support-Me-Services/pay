<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Mail\JobApplicationReceived;
use App\Modules\Storefront\Models\JobApplication;
use App\Modules\Storefront\Models\JobPosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CareersController extends Controller
{
    /** Hashowany URL wspólnego arkusza podstron (cache-bust). */
    private function subpagesCss(): string
    {
        return asset('css/subpages.css') . '?v=' . substr(md5_file(public_path('css/subpages.css')), 0, 10);
    }

    /** Czy oferta jest zdalna/hybrydowa (model nie ma pola — wykrywamy z tekstu). */
    private function isRemote(JobPosition $p): bool
    {
        $haystack = mb_strtolower(trim(($p->location ?? '') . ' ' . ($p->employment_type ?? '')));

        return $haystack !== '' && Str::contains($haystack, ['zdaln', 'remote', 'hybryd']);
    }

    /**
     * GET /praca — lista aktywnych stanowisk pracy.
     */
    public function index()
    {
        $positions = JobPosition::where('active', true)
            ->orderBy('sort')->orderBy('id')->get();

        $fallback = 'Dołącz do zespołu, który tworzy płatności NFC dla dobra wspólnego — technologię wspierającą parafie, fundacje i lokalne inicjatywy. Szukamy osób, które chcą łączyć nowoczesne rozwiązania z realnym wpływem na ludzi.';

        return Inertia::render('Storefront/Praca', [
            'positions' => $positions->map(function (JobPosition $p) use ($fallback) {
                $short = trim((string) $p->short_description);

                return [
                    'id' => $p->id,
                    'title' => $p->title,
                    'employment_type' => $p->employment_type,
                    'location' => $p->location,
                    'is_remote' => $this->isRemote($p),
                    'excerpt' => $short !== '' ? $short : $fallback,
                    'show_url' => route('careers.show', $p),
                ];
            })->values(),
            'css' => $this->subpagesCss(),
            'pageTitle' => 'Praca — ' . config('shop.name'),
            'pageDescription' => 'Dołącz do zespołu — aktualne oferty pracy i wolontariatu.',
        ]);
    }

    /**
     * GET /praca/oferta/{position} — pojedyncza oferta pracy na osobnej podstronie.
     */
    public function show(JobPosition $position)
    {
        abort_unless($position->active, 404);

        $others = JobPosition::where('active', true)
            ->where('id', '!=', $position->id)
            ->orderBy('sort')->orderBy('id')->limit(3)->get();

        $plain = trim(strip_tags($position->description_html ?? ''));

        return Inertia::render('Storefront/Oferta', [
            'position' => [
                'title' => $position->title,
                'employment_type' => trim((string) $position->employment_type),
                'location' => trim((string) $position->location),
                'is_remote' => $this->isRemote($position),
                'description_html' => $plain !== '' ? $position->description_html : null,
                'apply_url' => route('careers.apply', $position),
            ],
            'others' => $others->map(fn (JobPosition $o) => [
                'title' => $o->title,
                'meta' => collect([$o->employment_type, $o->location])->filter()->implode(' · ') ?: 'Zobacz szczegóły',
                'show_url' => route('careers.show', $o),
            ])->values(),
            'careersUrl' => route('careers'),
            'css' => $this->subpagesCss(),
            'pageTitle' => $position->title . ' — Praca — SupportME',
            'pageDescription' => Str::limit($plain, 150) ?: 'Dołącz do zespołu SupportME — technologia, która pomaga czynić dobro.',
        ]);
    }

    /**
     * GET /praca/aplikuj — formularz aplikacji spontanicznej (bez oferty).
     * GET /praca/{position}/aplikuj — formularz aplikacji na konkretną ofertę.
     */
    public function applyForm(?JobPosition $position = null)
    {
        $hasPosition = $position && $position->exists;

        return Inertia::render('Storefront/Aplikuj', [
            'position' => $hasPosition ? ['title' => $position->title] : null,
            'storeUrl' => $hasPosition ? route('careers.apply.store', $position) : route('careers.apply.general.store'),
            'careersUrl' => route('careers'),
            'css' => $this->subpagesCss(),
            'pageTitle' => ($hasPosition ? 'Aplikuj: ' . $position->title : 'Aplikacja spontaniczna') . ' — ' . config('shop.name'),
            'pageDescription' => 'Wyślij swoje zgłoszenie rekrutacyjne wraz z CV.',
        ]);
    }

    /**
     * POST /praca/aplikuj oraz POST /praca/{position}/aplikuj — zapis zgłoszenia.
     * CV przechowywane jest na PRYWATNYM dysku (storage/app/private/cv).
     */
    public function applyStore(Request $request, ?JobPosition $position = null)
    {
        // Honeypot antyspamowy — boty wypełniają ukryte pole "website".
        // Udajemy sukces (bez zapisu pliku/wpisu/maila), by nie zdradzać mechanizmu.
        if ($request->filled('website')) {
            return ($position && $position->exists
                ? redirect()->route('careers.apply', $position)
                : redirect()->route('careers.apply.general'))
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
        // wraz z datą jej udzielenia (potrzebna do liczenia okresu 24 mies.).
        $futureConsent = $request->boolean('future_consent');

        JobApplication::create([
            'job_position_id' => $position && $position->exists ? $position->id : null,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'] ?? null,
            'cv_path' => $cvPath,
            'cv_original_name' => $cvOriginalName,
            'future_recruitment_consent' => $futureConsent,
            'future_recruitment_consent_at' => $futureConsent ? now() : null,
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
                    'position' => $position && $position->exists ? $position->title : null,
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
        $redirect = $position && $position->exists
            ? redirect()->route('careers.apply', $position)
            : redirect()->route('careers.apply.general');

        return $redirect
            ->with('success', 'Dziękujemy za zgłoszenie — odezwiemy się.')
            ->with('apply_done', true);
    }
}
