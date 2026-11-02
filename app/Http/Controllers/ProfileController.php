<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Profile/Edit');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ], [
            'current_password.current_password' => 'Password saat ini tidak sesuai.',
            'password.different' => 'Password baru harus berbeda dari password saat ini.',
        ]);

        $request->user()->update(['password' => $data['password']]);

        ActivityLog::record('auth.password', 'Mengganti password akun');

        return back()->with('success', 'Password berhasil diganti.');
    }
}
