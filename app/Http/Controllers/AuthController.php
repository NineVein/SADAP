<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\KunciAkunService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login', ['kontak' => config('diskat.kontak_admin')]);
    }

    public function store(Request $request, KunciAkunService $kunci): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', Str::lower($credentials['email']))->first();

        // Akun yang sudah diblokir ditolak walau password benar. Hanya admin yang bisa membukanya.
        if ($user && $kunci->terblokir($user)) {
            throw ValidationException::withMessages(['email' => $kunci->pesanBlokir()]);
        }

        $key = Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        // is_active ikut menjadi syarat login, jadi akun nonaktif tidak bisa masuk
        // dan pesannya sama dengan password salah (tidak membocorkan status akun).
        $attempt = Auth::attempt(
            [...$credentials, 'is_active' => true],
            $request->boolean('remember')
        );

        if (! $attempt) {
            RateLimiter::hit($key, 60);

            // Hitung kegagalan untuk akun yang ada. Akun admin hanya dicatat, tidak pernah diblokir.
            if ($user && $user->is_active && $kunci->catatGagal($user)) {
                throw ValidationException::withMessages(['email' => $kunci->pesanBlokir()]);
            }

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah, atau akun tidak aktif.',
            ]);
        }

        RateLimiter::clear($key);
        $kunci->reset($request->user());
        $request->session()->regenerate();

        ActivityLog::record('auth.login', 'Berhasil masuk ke sistem');

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        ActivityLog::record('auth.logout', 'Keluar dari sistem');

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
