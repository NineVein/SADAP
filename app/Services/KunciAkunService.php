<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;

/**
 * Blokir akun setelah beberapa kali salah memasukkan password.
 *
 * - Akun diblokir otomatis setelah N kali gagal berturut-turut (config diskat.kunci_akun.maks_gagal).
 * - HANYA admin yang bisa membuka blokir (menu Pengaturan > Pengguna > Buka Blokir).
 * - Akun ADMIN tidak pernah diblokir. Percobaan gagalnya tetap dicatat di log aktivitas
 *   dan tetap dibatasi kecepatannya oleh pembatas laju di AuthController (jeda, bukan blokir).
 */
class KunciAkunService
{
    public function aktif(): bool
    {
        return (bool) config('diskat.kunci_akun.aktif', true);
    }

    /** Admin dikecualikan dari blokir agar selalu ada yang bisa membuka blokir akun lain. */
    public function dikecualikan(User $user): bool
    {
        return $user->isAdmin();
    }

    public function terblokir(User $user): bool
    {
        return $this->aktif() && ! $this->dikecualikan($user) && $user->terblokir_at !== null;
    }

    /**
     * Catat satu percobaan gagal.
     *
     * @return bool true bila akun BARU SAJA terblokir oleh percobaan ini
     */
    public function catatGagal(User $user): bool
    {
        if (! $this->aktif()) {
            return false;
        }

        ActivityLog::record('auth.login_gagal', "Percobaan masuk gagal untuk akun {$user->email}", null, $user->id);

        if ($this->dikecualikan($user)) {
            return false;
        }

        // Hitungan dimulai dari awal bila percobaan gagal terakhir sudah lama (salah ketik wajar tidak menumpuk).
        $batas = now()->subHours((int) config('diskat.kunci_akun.reset_setelah_jam', 24));
        $jumlah = ($user->terakhir_gagal_at === null || $user->terakhir_gagal_at->lt($batas))
            ? 1
            : $user->login_gagal + 1;

        $blokir = $jumlah >= (int) config('diskat.kunci_akun.maks_gagal', 5);

        $user->forceFill([
            'login_gagal' => $jumlah,
            'terakhir_gagal_at' => now(),
            'terblokir_at' => $blokir ? now() : $user->terblokir_at,
        ])->save();

        if ($blokir) {
            ActivityLog::record('auth.diblokir', "Akun {$user->email} diblokir setelah {$jumlah} kali gagal masuk", $user, $user->id);
        }

        return $blokir;
    }

    /** Dipanggil saat login berhasil: hitungan gagal kembali ke nol. */
    public function reset(User $user): void
    {
        if ($user->login_gagal > 0 || $user->terakhir_gagal_at !== null) {
            $user->forceFill(['login_gagal' => 0, 'terakhir_gagal_at' => null])->save();
        }
    }

    /** Membuka blokir. Hanya dipanggil dari tindakan admin. */
    public function buka(User $user): void
    {
        $user->forceFill(['login_gagal' => 0, 'terakhir_gagal_at' => null, 'terblokir_at' => null])->save();
    }

    /** Pesan untuk pengguna yang diblokir, lengkap dengan cara menghubungi administrator. */
    public function pesanBlokir(): string
    {
        $k = (array) config('diskat.kontak_admin');

        $cara = array_values(array_filter([
            filled($k['kantor'] ?? null) ? 'datang ke '.$k['kantor'] : null,
            filled($k['wa'] ?? null) ? 'WhatsApp '.$k['wa'] : null,
            filled($k['telepon'] ?? null) ? 'telepon '.$k['telepon'] : null,
        ]));

        return 'Akun Anda diblokir karena terlalu banyak percobaan masuk yang gagal. Hubungi administrator untuk membukanya'
            .($cara ? ': '.implode(', atau ', $cara).'.' : '.');
    }
}
