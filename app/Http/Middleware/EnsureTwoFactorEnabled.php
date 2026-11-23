<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Peran yang diwajibkan (config/diskat.php) harus mengaktifkan 2FA dulu.
 * Selama belum, satu-satunya halaman yang bisa dibuka adalah pengaturan keamanan, profil, dan keluar.
 */
class EnsureTwoFactorEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && $user->mustUseTwoFactor()
            && ! $user->hasTwoFactor()
            && ! $request->routeIs('keamanan.*', 'logout', 'profil.*')
        ) {
            return redirect()->route('keamanan.index')->with(
                'error',
                'Peran Anda wajib memakai verifikasi dua langkah. Aktifkan sekarang untuk melanjutkan.'
            );
        }

        return $next($request);
    }
}
