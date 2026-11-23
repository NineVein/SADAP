<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Akun yang dinonaktifkan admin langsung dikeluarkan,
 * walaupun sesi login-nya masih berlaku.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ($user->isBlocked() || ! $user->is_active)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => $user->isBlocked()
                    ? app(\App\Services\KunciAkunService::class)->pesanBlokir()
                    : 'Akun Anda tidak aktif. Hubungi administrator Dinas Sosial.',
            ]);
        }

        return $next($request);
    }
}
