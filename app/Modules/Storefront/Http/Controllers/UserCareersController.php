<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Models\User;
use App\Modules\Storefront\Mail\JobApplicationReceived;
use App\Modules\Storefront\Models\JobApplication;
use App\Modules\Storefront\Models\JobPosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * „Praca" per-konto (/people/{handle}/praca) — odpowiednik globalnego
 * CareersController, scoped przez właściciela wskazanego handle.
 */
class UserCareersController extends Controller
{
    private function subpagesCss(): string
    {
        return asset('css/subpages.css') . '?v=' . substr(md5_file(public_path('css/subpages.css')), 0, 10);
    }

    private function isRemote(JobPosition $p): bool
    {
        $haystack = mb_strtolower(trim(($p->location ?? '') . ' ' . ($p->employment_type ?? '')));

        return $haystack !== '' && Str::contains($haystack, ['zdaln', 'remote', 'hybryd']);
    }

    /** GET /people/{handle}/praca — lista aktywnych stanowisk tego właściciela. */
    public function index(string $handle)
    {
        $owner = User::where('handle', $handle)->firstOrFail();
        $positions = JobPosition::forUser($owner->id)->where('active', true)
            ->orderBy('sort')->orderBy('id')->get();

        $fallback = 'Dołącz do zespołu, który tworzy płatności NFC dla dobra wspólnego — technologię wspierającą parafie, fundacje i lokalne inicjatywy. Szukamy osób, które chcą łączyć nowoczesne rozwiązania z realnym wpływem na ludzi.';

        return Inertia::render('Storefront/Praca', [
            'positions' => $positions->map(function (JobPosition $p) use ($fallback, $handle) {
                $short = trim((string) $p->short_description);

                return [
                    'id' => $p->id,
                    'title' => $p->title,
                    'employment_type' => $p->employment_type,
                    'location' => $p->location,
                    'is_remote' => $this->isRemote($p),
                    'excerpt' => $short !== '' ? $short : $fallback,
                    'show_url' => route('user.careers.show', [$handle, $p]),
                ];
            })->values(),
            'css' => $this->subpagesCss(),
            'pageTitle' => 'Praca — ' . $owner->name,
            'pageDescription' => 'Dołącz do zespołu ' . $owner->name . ' — aktualne oferty pracy i wolontariatu.',
        ]);
    }

    /** GET /people/{handle}/praca/oferta/{position} — pojedyncza oferta. */
    public function show(string $handle, JobPosition $position)
    {
        $owner = User::where('handle', $handle)->firstOrFail();
        abort_unless($position->active && (int) $position->user_id === $owner->id, 404);

        $others = JobPosition::forUser($owner->id)->where('active', true)
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
                'apply_url' => route('user.careers.apply', [$handle, $position]),
            ],
            'others' => $others->map(fn (JobPosition $o) => [
                'title' => $o->title,
                'meta' => collect([$o->employment_type, $o->location])->filter()->implode(' · ') ?: 'Zobacz szczegóły',
                'show_url' => route('user.careers.show', [$handle, $o]),
            ])->values(),
            'careersUrl' => route('user.careers', $handle),
            'css' => $this->subpagesCss(),
            'pageTitle' => $position->title . ' — Praca — ' . $owner->name,
            'pageDescription' => Str::limit($plain, 150) ?: 'Dołącz do zespołu ' . $owner->name . '.',
        ]);
    }

    /**
     * GET /people/{handle}/praca/aplikuj — formularz spontaniczny.
     * GET /people/{handle}/praca/{position}/aplikuj — formularz na konkretną ofertę.
     */
    public function applyForm(string $handle, ?JobPosition $position = null)
    {
        $owner = User::where('handle', $handle)->firstOrFail();
        $hasPosition = $position && $position->exists;
        if ($hasPosition) {
            abort_unless((int) $position->user_id === $owner->id, 404);
        }

        return Inertia::render('Storefront/Aplikuj', [
            'position' => $hasPosition ? ['title' => $position->title] : null,
            'storeUrl' => $hasPosition ? route('user.careers.apply.store', [$handle, $position]) : route('user.careers.apply.general.store', $handle),
            'careersUrl' => route('user.careers', $handle),
            'css' => $this->subpagesCss(),
            'pageTitle' => ($hasPosition ? 'Aplikuj: ' . $position->title : 'Aplikacja spontaniczna') . ' — ' . $owner->name,
            'pageDescription' => 'Wyślij swoje zgłoszenie rekrutacyjne wraz z CV.',
        ]);
    }

    /**
     * POST /people/{handle}/praca/aplikuj oraz .../{position}/aplikuj — zapis zgłoszenia.
     * Mirror CareersController::applyStore, scoped przez handle.
     */
    public function applyStore(Request $request, string $handle, ?JobPosition $position = null)
    {
        $owner = User::where('handle', $handle)->firstOrFail();
        $hasPosition = $position && $position->exists;
        if ($hasPosition) {
            abort_unless((int) $position->user_id === $owner->id, 404);
        }

        if ($request->filled('website')) {
            return ($hasPosition
                ? redirect()->route('user.careers.apply', [$handle, $position])
                : redirect()->route('user.careers.apply.general', $handle))
                ->with('success', 'Dziękujemy za zgłoszenie — odezwiemy się.')
                ->with('apply_done', true);
        }

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
        $cvPath = Storage::disk('local')->putFile('cv', $file);
        $cvOriginalName = $file->getClientOriginalName();

        $futureConsent = $request->boolean('future_consent');

        JobApplication::create([
            'user_id' => $hasPosition ? $position->user_id : $owner->id,
            'job_position_id' => $hasPosition ? $position->id : null,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'] ?? null,
            'cv_path' => $cvPath,
            'cv_original_name' => $cvOriginalName,
            'future_recruitment_consent' => $futureConsent,
            'future_recruitment_consent_at' => $futureConsent ? now() : null,
        ]);

        if (! in_array(config('mail.default'), ['log', 'array', null], true)) {
            try {
                Mail::to(config('shop.careers_email'))->send(new JobApplicationReceived(
                    data: [
                        'name' => $data['name'],
                        'email' => $data['email'],
                        'phone' => $data['phone'] ?? null,
                        'message' => $data['message'] ?? null,
                        'position' => $hasPosition ? $position->title : null,
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

        $redirect = $hasPosition
            ? redirect()->route('user.careers.apply', [$handle, $position])
            : redirect()->route('user.careers.apply.general', $handle);

        return $redirect
            ->with('success', 'Dziękujemy za zgłoszenie — odezwiemy się.')
            ->with('apply_done', true);
    }
}
