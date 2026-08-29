<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Models\User;
use App\Modules\Storefront\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

/**
 * Samodzielne zakładanie konta sklepu (panel storefront).
 *
 * Konto od razu dostaje własny `handle` (generowany z nazwy przez User::booted),
 * więc po rejestracji sklep jest dostępny publicznie pod /people/{handle}.
 * Bez weryfikacji e-mail — kolumna email_verified_at pozostaje nieużywana,
 * tak jak w reszcie aplikacji.
 */
class RegisterController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return redirect()->route('panel.dashboard');
        }

        return Inertia::render('Panel/Auth/Register', [
            'brand' => config('shop.name', 'SupportME'),
            'postUrl' => route('panel.register.post'),
            'loginUrl' => route('panel.login'),
        ]);
    }

    public function store(Request $request)
    {
        // Zalogowany nie zakłada kolejnego konta — POST bez tego przelogowałby
        // go na świeżo utworzone konto (Auth::login niżej).
        if (Auth::check()) {
            return redirect()->route('panel.dashboard');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            // users.email jest unikalny globalnie (jedna tabela kont dla obu paneli).
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [
            // Aplikacja nie ma katalogu lang/ — komunikaty dla publicznego
            // formularza podajemy wprost, żeby nie mieszać języków.
            'required' => 'Pole :attribute jest wymagane.',
            'email.email' => 'Podaj poprawny adres e-mail.',
            'email.unique' => 'Konto z tym adresem e-mail już istnieje.',
            'password.confirmed' => 'Hasła nie są takie same.',
            'password.min' => 'Hasło musi mieć co najmniej 8 znaków.',
        ], [
            'name' => 'nazwa',
            'email' => 'e-mail',
            'password' => 'hasło',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->route('panel.dashboard')
            ->with('success', 'Konto zostało założone. Twój sklep jest pod adresem /people/'.$user->handle);
    }
}
