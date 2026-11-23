<?php

namespace App\Services;

use App\Mail\KodeOtpMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;

/**
 * Verifikasi dua langkah lewat kode OTP yang dikirim ke email pengguna.
 *
 * - Kode 6 digit, berlaku 10 menit, hanya sekali pakai.
 * - Yang disimpan di cache hanya HMAC kode, bukan kode aslinya.
 * - Setiap kode dibatasi 5 kali salah; setelah itu kode dihanguskan dan harus minta baru.
 * - Kirim ulang dibatasi jeda 60 detik dan maksimal 5 kali per 15 menit.
 */
class TwoFactorService
{
    public const TUJUAN_LOGIN = 'login';

    public const TUJUAN_AKTIVASI = 'aktivasi';

    public const BERLAKU_MENIT = 10;

    public const JEDA_KIRIM_DETIK = 60;

    public const MAKS_KIRIM = 5;

    public const MAKS_SALAH = 5;

    /** Membuat kode baru dan mengirimnya. Mengembalikan false bila masih dalam jeda/batas kirim. */
    public function kirim(User $user, string $tujuan): bool
    {
        if ($this->sisaJeda($user, $tujuan) > 0 || $this->terlaluBanyakKirim($user, $tujuan)) {
            return false;
        }

        $kode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        Cache::put($this->kunciKode($user, $tujuan), [
            'hash' => $this->hash($user, $kode),
            'salah' => 0,
        ], now()->addMinutes(self::BERLAKU_MENIT));

        Cache::put($this->kunciJeda($user, $tujuan), now()->addSeconds(self::JEDA_KIRIM_DETIK)->timestamp, now()->addSeconds(self::JEDA_KIRIM_DETIK));

        $kunciHitung = $this->kunciHitung($user, $tujuan);
        Cache::add($kunciHitung, 0, now()->addMinutes(15));
        Cache::increment($kunciHitung);

        try {
            Mail::to($user->email)->send(new KodeOtpMail($user, $kode, $tujuan, self::BERLAKU_MENIT));
        } catch (\Throwable $e) {
            // Email gagal: jangan tinggalkan kode/jeda yang membuat percobaan berikutnya terlihat "terkirim".
            $this->hapus($user, $tujuan);

            throw $e;
        }

        return true;
    }

    /** Memeriksa kode. Kode benar langsung dihapus sehingga tidak bisa dipakai lagi. */
    public function verifikasi(User $user, string $tujuan, string $kode): bool
    {
        $kode = preg_replace('/\D/', '', $kode);
        $kunci = $this->kunciKode($user, $tujuan);
        $data = Cache::get($kunci);

        if (! $data || strlen($kode) !== 6) {
            return false;
        }

        if (! hash_equals($data['hash'], $this->hash($user, $kode))) {
            $data['salah']++;

            if ($data['salah'] >= self::MAKS_SALAH) {
                Cache::forget($kunci);
            } else {
                Cache::put($kunci, $data, now()->addMinutes(self::BERLAKU_MENIT));
            }

            return false;
        }

        Cache::forget($kunci);

        return true;
    }

    public function adaKodeAktif(User $user, string $tujuan): bool
    {
        return Cache::has($this->kunciKode($user, $tujuan));
    }

    /** Detik tersisa sebelum boleh kirim ulang. */
    public function sisaJeda(User $user, string $tujuan): int
    {
        $sampai = (int) Cache::get($this->kunciJeda($user, $tujuan), 0);

        return max(0, $sampai - now()->timestamp);
    }

    public function terlaluBanyakKirim(User $user, string $tujuan): bool
    {
        return (int) Cache::get($this->kunciHitung($user, $tujuan), 0) >= self::MAKS_KIRIM;
    }

    public function hapus(User $user, string $tujuan): void
    {
        Cache::forget($this->kunciKode($user, $tujuan));
        Cache::forget($this->kunciJeda($user, $tujuan));
        Cache::forget($this->kunciHitung($user, $tujuan));
    }

    /** Email dengan sebagian karakter disamarkan, mis. a***@gmail.com */
    public function samarkanEmail(string $email): string
    {
        [$nama, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($nama, 0, 1).str_repeat('*', max(2, mb_strlen($nama) - 1)).'@'.$domain;
    }

    private function hash(User $user, string $kode): string
    {
        return hash_hmac('sha256', $user->id.':'.$kode, (string) config('app.key'));
    }

    private function kunciKode(User $user, string $tujuan): string
    {
        return "otp:kode:{$tujuan}:{$user->id}";
    }

    private function kunciJeda(User $user, string $tujuan): string
    {
        return "otp:jeda:{$tujuan}:{$user->id}";
    }

    private function kunciHitung(User $user, string $tujuan): string
    {
        return "otp:hitung:{$tujuan}:{$user->id}";
    }
}
