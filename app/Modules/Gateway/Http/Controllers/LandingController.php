<?php

namespace App\Modules\Gateway\Http\Controllers;

use App\Modules\Gateway\Models\Lead;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function index()
    {
        return view('landing');
    }

    public function storeLead(Request $request)
    {
        // Honeypot antyspamowy — boty wypełniają ukryte pole "website".
        if ($request->filled('website')) {
            return redirect()->route('landing')->with('lead_ok', true);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ], [], [
            'name' => 'imię i nazwisko',
            'email' => 'e-mail',
            'phone' => 'telefon',
            'company' => 'firma',
            'message' => 'wiadomość',
        ]);

        Lead::create($data);

        return redirect()->route('landing')->with('lead_ok', true)->withFragment('kontakt');
    }
}
