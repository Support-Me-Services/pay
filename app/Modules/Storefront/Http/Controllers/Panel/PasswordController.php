<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class PasswordController extends Controller
{
    /** Ekran „Zarządzanie kontem" — dane konta, zmiana hasła, wylogowanie. */
    public function edit(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Panel/Account/Index', [
            'account' => [
                'name' => $user->name,
                'email' => $user->email,
                'isAdmin' => (bool) $user->is_admin,
                'createdAt' => $user->created_at?->format('d.m.Y'),
            ],
            'updateUrl' => route('panel.password.update'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [], [
            'current_password' => 'obecne hasło',
            'password' => 'nowe hasło',
        ]);

        $request->user()->update(['password' => Hash::make($data['password'])]);

        return back()->with('success', 'Hasło zostało zmienione.');
    }
}
