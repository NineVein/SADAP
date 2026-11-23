<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AuthController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $key = Str::transliterate(Str::lower($credentials['email']).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan login. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        // is_active ikut menjadi syarat login, jadi akun nonaktif tidak bisa masuk
        // dan pesannya sama dengan password salah (tidak membocorkan status akun).
        $syarat = [...$credentials, 'is_active' => true];

        if (! Auth::validate($syarat)) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages([
                'email' => 'Email atau password salah, atau akun tidak aktif.',
            ]);
        }

        RateLimiter::clear($key);

        /** @var User $user */
        $user = Auth::getProvider()->retrieveByCredentials($syarat);

        // Password benar tetapi akun memakai 2FA: belum masuk, kirim kode OTP ke email dulu.
        if ($user->hasTwoFactor()) {
            try {
                app(TwoFactorService::class)->kirim($user, TwoFactorService::TUJUAN_LOGIN);
            } catch (Throwable $e) {
                report($e);

                throw ValidationException::withMessages([
                    'email' => 'Kode verifikasi tidak dapat dikirim ke email. Coba lagi atau hubungi administrator.',
                ]);
            }

            $request->session()->regenerate();
            $request->session()->put('login.2fa', [
                'id' => $user->id,
                'remember' => $request->boolean('remember'),
                'ts' => now()->timestamp,
            ]);

            return redirect()->route('2fa.challenge');
        }

        Auth::login($user, $request->boolean('remember'));
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
