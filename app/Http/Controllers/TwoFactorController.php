<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class TwoFactorController extends Controller
{
    private const BATAS_WAKTU_DETIK = 600;

    public function __construct(private TwoFactorService $service) {}

    /* ---------------------------- Tantangan saat login ---------------------------- */

    public function challenge(Request $request): Response|RedirectResponse
    {
        $pending = $this->pending($request);

        if (! $pending) {
            return redirect()->route('login');
        }

        $user = User::find($pending['id']);

        if (! $user) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/TwoFactorChallenge', [
            'email' => $this->service->samarkanEmail($user->email),
            'sisaJeda' => $this->service->sisaJeda($user, TwoFactorService::TUJUAN_LOGIN),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $pending = $this->pending($request);

        if (! $pending) {
            $request->session()->forget('login.2fa');

            return redirect()->route('login')->withErrors(['email' => 'Sesi verifikasi berakhir. Silakan masuk kembali.']);
        }

        $user = User::find($pending['id']);

        if (! $user || ! $user->is_active || ! $user->hasTwoFactor()) {
            $request->session()->forget('login.2fa');

            return redirect()->route('login')->withErrors(['email' => 'Akun tidak dapat diverifikasi. Silakan masuk kembali.']);
        }

        $key = '2fa:'.$user->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'kode' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
            ]);
        }

        $data = $request->validate(['kode' => ['required', 'string', 'max:20']], [
            'kode.required' => 'Masukkan 6 digit kode dari email.',
        ]);

        if (! $this->service->verifikasi($user, TwoFactorService::TUJUAN_LOGIN, $data['kode'])) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['kode' => 'Kode salah atau sudah kedaluwarsa.']);
        }

        RateLimiter::clear($key);
        $this->service->hapus($user, TwoFactorService::TUJUAN_LOGIN);
        $request->session()->forget('login.2fa');

        Auth::login($user, (bool) $pending['remember']);
        $request->session()->regenerate();

        ActivityLog::record('auth.login', 'Berhasil masuk dengan verifikasi dua langkah (OTP email)', null, $user->id);

        return redirect()->intended(route('dashboard'));
    }

    public function kirimUlang(Request $request): RedirectResponse
    {
        $pending = $this->pending($request);
        $user = $pending ? User::find($pending['id']) : null;

        if (! $user || ! $user->is_active || ! $user->hasTwoFactor()) {
            return redirect()->route('login');
        }

        return $this->kirimDanKabari($user, TwoFactorService::TUJUAN_LOGIN, 'kode');
    }

    public function batal(Request $request): RedirectResponse
    {
        $pending = $this->pending($request);

        if ($pending && ($user = User::find($pending['id']))) {
            $this->service->hapus($user, TwoFactorService::TUJUAN_LOGIN);
        }

        $request->session()->forget('login.2fa');

        return redirect()->route('login');
    }

    /* ---------------------------- Pengaturan keamanan akun ---------------------------- */

    public function index(Request $request): Response
    {
        $user = $request->user();
        $menunggu = ! $user->hasTwoFactor() && $this->service->adaKodeAktif($user, TwoFactorService::TUJUAN_AKTIVASI);

        return Inertia::render('Keamanan/Index', [
            'aktif' => $user->hasTwoFactor(),
            'wajib' => $user->mustUseTwoFactor(),
            'emailTujuan' => $this->service->samarkanEmail($user->email),
            'menungguKonfirmasi' => $menunggu,
            'sisaJeda' => $this->service->sisaJeda($user, TwoFactorService::TUJUAN_AKTIVASI),
            'aktifSejak' => $user->two_factor_confirmed_at?->translatedFormat('d F Y H:i'),
        ]);
    }

    /** Langkah 1 aktivasi: kirim kode ke email akun. */
    public function aktifkan(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasTwoFactor()) {
            return back()->with('error', 'Verifikasi dua langkah sudah aktif.');
        }

        return $this->kirimDanKabari($user, TwoFactorService::TUJUAN_AKTIVASI, 'kode');
    }

    /** Langkah 2 aktivasi: kode dari email benar, maka 2FA aktif. */
    public function konfirmasi(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate(['kode' => ['required', 'string', 'max:20']], [
            'kode.required' => 'Masukkan 6 digit kode dari email.',
        ]);

        if ($user->hasTwoFactor()) {
            return redirect()->route('keamanan.index');
        }

        $key = '2fa-aktivasi:'.$user->id;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'kode' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
            ]);
        }

        if (! $this->service->verifikasi($user, TwoFactorService::TUJUAN_AKTIVASI, $data['kode'])) {
            RateLimiter::hit($key, 60);

            throw ValidationException::withMessages(['kode' => 'Kode salah atau sudah kedaluwarsa. Kirim ulang kode bila perlu.']);
        }

        RateLimiter::clear($key);
        $this->service->hapus($user, TwoFactorService::TUJUAN_AKTIVASI);
        $user->forceFill(['two_factor_confirmed_at' => now()])->save();

        ActivityLog::record('auth.2fa_aktif', 'Mengaktifkan verifikasi dua langkah (OTP email)');

        return redirect()->route('keamanan.index')->with('success', 'Verifikasi dua langkah aktif. Mulai sekarang setiap login meminta kode dari email Anda.');
    }

    public function nonaktifkan(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'current_password']], [
            'password.current_password' => 'Password tidak sesuai.',
        ]);

        $user = $request->user();

        if ($user->mustUseTwoFactor()) {
            return back()->with('error', 'Peran Anda wajib memakai verifikasi dua langkah, jadi tidak bisa dinonaktifkan.');
        }

        $user->forceFill(['two_factor_confirmed_at' => null])->save();
        $this->service->hapus($user, TwoFactorService::TUJUAN_AKTIVASI);

        ActivityLog::record('auth.2fa_nonaktif', 'Menonaktifkan verifikasi dua langkah');

        return redirect()->route('keamanan.index')->with('success', 'Verifikasi dua langkah dinonaktifkan.');
    }

    private function kirimDanKabari(User $user, string $tujuan, string $field): RedirectResponse
    {
        if ($this->service->terlaluBanyakKirim($user, $tujuan)) {
            throw ValidationException::withMessages([$field => 'Terlalu sering meminta kode. Coba lagi beberapa menit lagi.']);
        }

        if ($sisa = $this->service->sisaJeda($user, $tujuan)) {
            throw ValidationException::withMessages([$field => "Tunggu {$sisa} detik sebelum meminta kode baru."]);
        }

        try {
            $this->service->kirim($user, $tujuan);
        } catch (Throwable $e) {
            report($e);
            Log::warning('Gagal mengirim OTP email', ['user_id' => $user->id, 'galat' => $e::class]);
            $this->service->hapus($user, $tujuan);

            throw ValidationException::withMessages([
                $field => 'Email tidak dapat dikirim saat ini. Hubungi administrator.',
            ]);
        }

        return back()->with('success', 'Kode dikirim ke '.$this->service->samarkanEmail($user->email).'. Berlaku '.TwoFactorService::BERLAKU_MENIT.' menit.');
    }

    /** @return array{id:int, remember:bool, ts:int}|null */
    private function pending(Request $request): ?array
    {
        $p = $request->session()->get('login.2fa');

        if (! is_array($p) || (now()->timestamp - ($p['ts'] ?? 0)) > self::BATAS_WAKTU_DETIK) {
            return null;
        }

        return $p;
    }
}
