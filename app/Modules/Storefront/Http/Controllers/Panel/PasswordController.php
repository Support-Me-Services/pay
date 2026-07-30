<?php

namespace App\Modules\Storefront\Http\Controllers\Panel;

use App\Modules\Storefront\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;

class PasswordController extends Controller
{
    public function edit()
    {
        return Inertia::render('Panel/Auth/ChangePassword', [
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
