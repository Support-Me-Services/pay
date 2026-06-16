<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Mail\JobApplicationReceived;
use App\Modules\Storefront\Models\JobApplication;
use App\Modules\Storefront\Models\JobPosition;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class CareersController extends Controller
{
    /**
     * GET /praca — lista aktywnych stanowisk pracy.
     */
    public function index()
    {
        $positions = JobPosition::where('active', true)
            ->orderBy('sort')
            ->orderBy('id')
            ->get();

        return view('shop.praca', compact('positions'));
    }

    /**
     * GET /praca/oferta/{position} — pojedyncza oferta pracy na osobnej podstronie.
     */
    public function show(JobPosition $position)
    {
        abort_unless($position->active, 404);

        $others = JobPosition::where('active', true)
            ->where('id', '!=', $position->id)
            ->orderBy('sort')->orderBy('id')
            ->limit(3)
            ->get();

        return view('shop.oferta', compact('position', 'others'));
    }

    /**
     * GET /praca/aplikuj — formularz aplikacji spontanicznej (bez oferty).
     * GET /praca/{position}/aplikuj — formularz aplikacji na konkretną ofertę.
     */
    public function applyForm(?JobPosition $position = null)
    {
        return view('shop.aplikuj', compact('position'));
    }

    /**
     * POST /praca/aplikuj oraz POST /praca/{position}/aplikuj — zapis zgłoszenia.
     * CV przechowywane jest na PRYWATNYM dysku (storage/app/private/cv).
     */
    public function applyStore(Request $request, ?JobPosition $position = null)
    {
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

        JobApplication::create([
            'job_position_id' => $position && $position->exists ? $position->id : null,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'] ?? null,
            'cv_path' => $cvPath,
            'cv_original_name' => $cvOriginalName,
        ]);

        // Wyślij zgłoszenie z CV w załączniku na skonfigurowany adres rekrutacji.
        // Błąd wysyłki NIE może zablokować zgłoszenia — jest już zapisane w bazie
        // i widoczne w panelu (Zgłoszenia). Logujemy ewentualny błąd.
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
