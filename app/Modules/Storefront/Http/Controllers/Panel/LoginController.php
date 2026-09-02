<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoginController extends Controller
{
    public function show()
    {
        if (Auth::check()) {
            return redirect()->route('panel.dashboard');
        }

        return Inertia::render('Panel/Auth/Login', [
            'brand' => config('shop.name', 'SupportME'),
            'postUrl' => route('panel.login.post'),
            'registerUrl' => route('panel.register'),
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ], [], ['email' => 'e-mail', 'password' => 'hasło']);

        // Logowanie niewrażliwe na wielkość liter w e-mailu.
        $credentials['email'] = strtolower(trim($credentials['email']));

        if (! Auth::attempt($credentials, true)) {
            return back()->withErrors(['email' => 'Niepoprawny login lub hasło.'])->onlyInput('email');
        }

        $request->session()->regenerate();

        return redirect()->intended(route('panel.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('panel.login');
    }
}
