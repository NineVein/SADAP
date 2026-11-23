<?php

namespace Tests\Feature;

use App\Mail\KodeOtpMail;
use App\Models\Role;
use App\Models\User;
use App\Services\TwoFactorService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Rahasia12345';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Mail::fake();
        Cache::flush();
    }

    private function user(string $role = Role::ADMIN, bool $dengan2fa = false): User
    {
        $user = User::factory()->create([
            'role_id' => Role::where('name', $role)->value('id'),
            'password' => self::PASSWORD,
            'is_active' => true,
        ]);

        if ($dengan2fa) {
            $user->forceFill(['two_factor_confirmed_at' => now()])->save();
        }

        return $user->refresh();
    }

    private function masuk(User $user): \Illuminate\Testing\TestResponse
    {
        return $this->post('/login', ['email' => $user->email, 'password' => self::PASSWORD]);
    }

    /** Mengambil kode terakhir yang dikirim ke pengguna. */
    private function kodeTerkirim(User $user, string $tujuan = TwoFactorService::TUJUAN_LOGIN): string
    {
        $kode = null;

        Mail::assertSent(KodeOtpMail::class, function (KodeOtpMail $m) use ($user, $tujuan, &$kode) {
            if ($m->hasTo($user->email) && $m->tujuan === $tujuan) {
                $kode = $m->kode;

                return true;
            }

            return false;
        });

        return $kode;
    }

    private function lewatiJeda(): void
    {
        $this->travel(61)->seconds();
    }

    public function test_tanpa_2fa_langsung_masuk_dan_tidak_ada_email(): void
    {
        $user = $this->user();

        $this->masuk($user)->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        Mail::assertNothingSent();
    }

    public function test_dengan_2fa_email_otp_dikirim_dan_belum_masuk(): void
    {
        $user = $this->user(Role::ADMIN, true);

        $this->masuk($user)->assertRedirect(route('2fa.challenge'));
        $this->assertGuest();
        Mail::assertSent(KodeOtpMail::class, 1);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $this->kodeTerkirim($user));
    }

    public function test_kode_benar_menyelesaikan_login(): void
    {
        $user = $this->user(Role::ADMIN, true);
        $this->masuk($user);

        $this->post('/verifikasi-2fa', ['kode' => $this->kodeTerkirim($user)])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_kode_salah_ditolak(): void
    {
        $user = $this->user(Role::ADMIN, true);
        $this->masuk($user);

        $this->post('/verifikasi-2fa', ['kode' => '000000'])->assertSessionHasErrors('kode');
        $this->assertGuest();
    }

    public function test_tantangan_tidak_bisa_dibuka_tanpa_login_password_dulu(): void
    {
        $this->get('/verifikasi-2fa')->assertRedirect(route('login'));
        $this->post('/verifikasi-2fa', ['kode' => '123456'])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_kode_hanya_berlaku_sekali(): void
    {
        $user = $this->user(Role::ADMIN, true);
        $this->masuk($user);
        $kode = $this->kodeTerkirim($user);

        $this->post('/verifikasi-2fa', ['kode' => $kode])->assertRedirect(route('dashboard'));
        $this->post('/logout');
        $this->assertGuest();

        // Login kedua memakai kode lama: harus ditolak karena kode baru sudah dibuat / yang lama sudah habis.
        $this->lewatiJeda();
        $this->masuk($user);
        $this->post('/verifikasi-2fa', ['kode' => $kode])->assertSessionHasErrors('kode');
        $this->assertGuest();
    }

    public function test_kode_kedaluwarsa_setelah_10_menit(): void
    {
        $user = $this->user(Role::ADMIN, true);
        $this->masuk($user);
        $kode = $this->kodeTerkirim($user);

        $this->travel(11)->minutes();

        // Sesi tantangan juga berumur 10 menit, jadi dikembalikan ke halaman masuk.
        $this->post('/verifikasi-2fa', ['kode' => $kode])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_kode_dihanguskan_setelah_lima_kali_salah(): void
    {
        $user = $this->user(Role::ADMIN, true);
        $this->masuk($user);
        $kode = $this->kodeTerkirim($user);

        $service = app(TwoFactorService::class);
        for ($i = 0; $i < 5; $i++) {
            $this->assertFalse($service->verifikasi($user, TwoFactorService::TUJUAN_LOGIN, '000000'));
        }

        // Kode yang tadinya benar sekarang sudah hangus.
        $this->assertFalse($service->verifikasi($user, TwoFactorService::TUJUAN_LOGIN, $kode));
    }

    public function test_pembatas_percobaan_memblokir_setelah_lima_kali_salah(): void
    {
        $user = $this->user(Role::ADMIN, true);
        $this->masuk($user);
        $kode = $this->kodeTerkirim($user);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/verifikasi-2fa', ['kode' => '000000'])->assertSessionHasErrors('kode');
        }

        $this->post('/verifikasi-2fa', ['kode' => $kode])->assertSessionHasErrors('kode');
        $this->assertGuest();
    }

    public function test_kirim_ulang_dibatasi_jeda_lalu_membuat_kode_baru(): void
    {
        $user = $this->user(Role::ADMIN, true);
        $this->masuk($user);
        $kodeLama = $this->kodeTerkirim($user);

        // Langsung kirim ulang: ditolak karena masih dalam jeda 60 detik.
        $this->post('/verifikasi-2fa/kirim-ulang')->assertSessionHasErrors('kode');
        Mail::assertSent(KodeOtpMail::class, 1);

        $this->lewatiJeda();
        $this->post('/verifikasi-2fa/kirim-ulang')->assertSessionHasNoErrors();
        Mail::assertSent(KodeOtpMail::class, 2);

        // Kode lama tidak berlaku lagi setelah kode baru dibuat (kecuali kebetulan sama).
        $kodeBaru = null;
        Mail::assertSent(KodeOtpMail::class, function (KodeOtpMail $m) use (&$kodeBaru) {
            $kodeBaru = $m->kode;

            return true;
        });

        if ($kodeBaru !== $kodeLama) {
            $this->post('/verifikasi-2fa', ['kode' => $kodeLama])->assertSessionHasErrors('kode');
        }
        $this->post('/verifikasi-2fa', ['kode' => $kodeBaru])->assertRedirect(route('dashboard'));
    }

    public function test_kirim_ulang_dibatasi_lima_kali(): void
    {
        $user = $this->user(Role::ADMIN, true);
        $service = app(TwoFactorService::class);

        for ($i = 0; $i < TwoFactorService::MAKS_KIRIM; $i++) {
            $this->assertTrue($service->kirim($user, TwoFactorService::TUJUAN_LOGIN));
            $this->lewatiJeda();
        }

        $this->assertFalse($service->kirim($user, TwoFactorService::TUJUAN_LOGIN));
    }

    public function test_kode_tidak_disimpan_sebagai_teks_asli(): void
    {
        $user = $this->user(Role::ADMIN, true);
        $this->masuk($user);
        $kode = $this->kodeTerkirim($user);

        $tersimpan = json_encode(Cache::get('otp:kode:login:'.$user->id));
        $this->assertStringNotContainsString($kode, $tersimpan);
    }

    public function test_kode_aktivasi_tidak_bisa_dipakai_untuk_login(): void
    {
        $user = $this->user(Role::ADMIN);
        $this->actingAs($user)->post('/keamanan/aktifkan');
        $kodeAktivasi = $this->kodeTerkirim($user, TwoFactorService::TUJUAN_AKTIVASI);

        $this->assertFalse(app(TwoFactorService::class)->verifikasi($user, TwoFactorService::TUJUAN_LOGIN, $kodeAktivasi));
    }

    public function test_aktivasi_mengirim_kode_lalu_konfirmasi_mengaktifkan(): void
    {
        $user = $this->user();

        $this->actingAs($user)->post('/keamanan/aktifkan')->assertSessionHas('success');
        $this->assertFalse($user->refresh()->hasTwoFactor());
        $kode = $this->kodeTerkirim($user, TwoFactorService::TUJUAN_AKTIVASI);

        $this->post('/keamanan/konfirmasi', ['kode' => '000000'])->assertSessionHasErrors('kode');
        $this->assertFalse($user->refresh()->hasTwoFactor());

        $this->post('/keamanan/konfirmasi', ['kode' => $kode])->assertRedirect(route('keamanan.index'));
        $this->assertTrue($user->refresh()->hasTwoFactor());
    }

    public function test_aktivasi_gagal_dengan_pesan_bila_email_tidak_terkirim(): void
    {
        $user = $this->user();
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

        $this->actingAs($user)->post('/keamanan/aktifkan')->assertSessionHasErrors('kode');
        $this->assertFalse($user->refresh()->hasTwoFactor());
    }

    public function test_login_ditolak_bila_email_otp_gagal_terkirim(): void
    {
        $user = $this->user(Role::ADMIN, true);
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('SMTP down'));

        $this->masuk($user)->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_peran_wajib_dipaksa_ke_halaman_keamanan_selama_belum_aktif(): void
    {
        config(['diskat.two_factor.wajib' => true]);
        $admin = $this->user(Role::ADMIN);

        $this->actingAs($admin)->get('/')->assertRedirect(route('keamanan.index'));
        $this->get('/keamanan')->assertOk();
    }

    public function test_peran_lain_tidak_dipaksa(): void
    {
        config(['diskat.two_factor.wajib' => true]);
        $operator = $this->user(Role::OPERATOR);

        $this->actingAs($operator)->get('/')->assertOk();
    }

    public function test_peran_wajib_tidak_bisa_menonaktifkan(): void
    {
        config(['diskat.two_factor.wajib' => true]);
        $admin = $this->user(Role::ADMIN, true);

        $this->actingAs($admin)->delete('/keamanan', ['password' => self::PASSWORD])->assertSessionHas('error');
        $this->assertTrue($admin->refresh()->hasTwoFactor());
    }

    public function test_peran_biasa_bisa_menonaktifkan_dengan_password(): void
    {
        $operator = $this->user(Role::OPERATOR, true);

        $this->actingAs($operator)->delete('/keamanan', ['password' => 'salah'])->assertSessionHasErrors('password');
        $this->assertTrue($operator->refresh()->hasTwoFactor());

        $this->delete('/keamanan', ['password' => self::PASSWORD])->assertRedirect(route('keamanan.index'));
        $this->assertFalse($operator->refresh()->hasTwoFactor());
    }

    public function test_admin_dapat_mereset_2fa_pengguna_lain(): void
    {
        $admin = $this->user(Role::ADMIN);
        $target = $this->user(Role::OPERATOR, true);

        $this->actingAs($admin)->post("/users/{$target->id}/reset-2fa")->assertSessionHas('success');
        $this->assertFalse($target->refresh()->hasTwoFactor());
    }

    public function test_supervisor_tidak_boleh_mereset_2fa(): void
    {
        $supervisor = $this->user(Role::SUPERVISOR);
        $target = $this->user(Role::OPERATOR, true);

        $this->actingAs($supervisor)->post("/users/{$target->id}/reset-2fa")->assertForbidden();
        $this->assertTrue($target->refresh()->hasTwoFactor());
    }

    public function test_api_login_pertama_mengirim_otp_lalu_kedua_dengan_kode_berhasil(): void
    {
        $user = $this->user(Role::ADMIN, true);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => self::PASSWORD])
            ->assertStatus(422)
            ->assertJsonPath('butuh_kode', true);
        $this->assertGuest();

        $kode = $this->kodeTerkirim($user);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => self::PASSWORD, 'kode' => '000000'])
            ->assertStatus(422);

        $this->postJson('/api/login', ['email' => $user->email, 'password' => self::PASSWORD, 'kode' => $kode])
            ->assertOk()
            ->assertJsonPath('user.email', $user->email);
    }

    public function test_samarkan_email(): void
    {
        $this->assertSame('b***@gmail.com', app(TwoFactorService::class)->samarkanEmail('budi@gmail.com'));
    }
}
