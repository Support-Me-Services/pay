<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Models\JobApplication;
use App\Modules\Storefront\Models\JobPosition;
use Illuminate\Http\Request;
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
        // CV jest wymagane dla aplikacji na konkretną ofertę.
        $cvRules = ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'];
        if ($position && $position->exists) {
            $cvRules[0] = 'required';
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'message' => ['nullable', 'string', 'max:5000'],
            'cv' => $cvRules,
        ], [], [
            'name' => 'imię i nazwisko',
            'email' => 'e-mail',
            'phone' => 'telefon',
            'message' => 'list motywacyjny',
            'cv' => 'plik CV',
        ]);

        $cvPath = null;
        $cvOriginalName = null;
        if ($request->hasFile('cv')) {
            $file = $request->file('cv');
            // Dysk 'local' (storage/app/private) — pliki NIE są publicznie dostępne.
            $cvPath = Storage::disk('local')->putFile('cv', $file);
            $cvOriginalName = $file->getClientOriginalName();
        }

        JobApplication::create([
            'job_position_id' => $position && $position->exists ? $position->id : null,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'message' => $data['message'] ?? null,
            'cv_path' => $cvPath,
            'cv_original_name' => $cvOriginalName,
        ]);

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
