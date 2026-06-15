<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Modules\Storefront\Models\ContactMessage;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * GET /kontakt — formularz kontaktowy. Temat może być wstępnie
     * wypełniony z parametru ?stanowisko= (link „Aplikuj" z sekcji Praca).
     */
    public function show(Request $request)
    {
        $stanowisko = trim((string) $request->query('stanowisko', ''));
        $subject = $stanowisko !== '' ? ('Aplikacja: ' . $stanowisko) : '';

        return view('shop.kontakt', compact('subject'));
    }

    /**
     * POST /kontakt — walidacja i zapis wiadomości; redirect z flashem.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:5000'],
        ], [], [
            'name' => 'imię i nazwisko',
            'email' => 'e-mail',
            'phone' => 'telefon',
            'subject' => 'temat',
            'message' => 'wiadomość',
        ]);

        ContactMessage::create($data);

        return redirect()->route('contact.show')
            ->with('success', 'Dziękujemy, odezwiemy się.');
    }
}
