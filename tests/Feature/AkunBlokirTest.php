<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AkunBlokirTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Rahasia12345';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        config(['diskat.kunci_akun.maks_gagal' => 3]);
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'role_id' => Role::where('name', $role)->value('id'),
            'password' => self::PASSWORD,
            'is_active' => true,
        ]);
    }

    private function gagal(User $user, int $kali = 1): void
    {
        for ($i = 0; $i < $kali; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'salah-sekali']);
        }
    }

    public function test_akun_diblokir_setelah_gagal_berulang(): void
    {
        $operator = $this->user(Role::OPERATOR);

        $this->gagal($operator, 2);
        $this->assertNull($operator->refresh()->terblokir_at);

        $this->post('/login', ['email' => $operator->email, 'password' => 'salah-lagi'])->assertSessionHasErrors('email');

        $this->assertStringContainsString('diblokir', session('errors')->first('email'));
        $this->assertNotNull($operator->refresh()->terblokir_at);
    }

    public function test_password_benar_tetap_ditolak_saat_diblokir(): void
    {
        $operator = $this->user(Role::OPERATOR);
        $this->gagal($operator, 3);

        $this->post('/login', ['email' => $operator->email, 'password' => self::PASSWORD])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_admin_tidak_pernah_diblokir(): void
    {
        $admin = $this->user(Role::ADMIN);

        $this->gagal($admin, 6);

        $admin->refresh();
        $this->assertNull($admin->terblokir_at);
        $this->assertSame(0, $admin->login_gagal);

        // Jeda pembatas laju hanya sementara, bukan blokir: setelah reda, admin bisa masuk.
        Cache::flush();
        $this->post('/login', ['email' => $admin->email, 'password' => self::PASSWORD])->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($admin);
    }

    public function test_login_berhasil_mereset_hitungan_gagal(): void
    {
        $operator = $this->user(Role::OPERATOR);
        $this->gagal($operator, 2);
        $this->assertSame(2, $operator->refresh()->login_gagal);

        $this->post('/login', ['email' => $operator->email, 'password' => self::PASSWORD])->assertRedirect();
        $this->assertSame(0, $operator->refresh()->login_gagal);
    }

    public function test_hitungan_kembali_nol_bila_gagal_terakhir_sudah_lama(): void
    {
        $operator = $this->user(Role::OPERATOR);
        $operator->forceFill(['login_gagal' => 2, 'terakhir_gagal_at' => now()->subDays(2)])->save();

        $this->gagal($operator, 1);

        $operator->refresh();
        $this->assertSame(1, $operator->login_gagal);
        $this->assertNull($operator->terblokir_at);
    }

    public function test_hanya_admin_yang_dapat_membuka_blokir(): void
    {
        $operator = $this->user(Role::OPERATOR);
        $this->gagal($operator, 3);
        $this->assertNotNull($operator->refresh()->terblokir_at);

        $supervisor = $this->user(Role::SUPERVISOR);
        $this->actingAs($supervisor)->post("/users/{$operator->id}/buka-blokir")->assertForbidden();
        $this->assertNotNull($operator->refresh()->terblokir_at);

        $this->actingAs($this->user(Role::ADMIN))->post("/users/{$operator->id}/buka-blokir")->assertSessionHas('success');
        $operator->refresh();
        $this->assertNull($operator->terblokir_at);
        $this->assertSame(0, $operator->login_gagal);

        $this->post('/logout');
        $this->post('/login', ['email' => $operator->email, 'password' => self::PASSWORD])->assertRedirect(route('dashboard'));
    }

    public function test_sesi_akun_yang_terblokir_diakhiri(): void
    {
        $operator = $this->user(Role::OPERATOR);
        $operator->forceFill(['terblokir_at' => now()])->save();

        $this->actingAs($operator)->get('/')->assertRedirect(route('login'));
    }

    public function test_pesan_blokir_memuat_kontak_administrator(): void
    {
        config(['diskat.kontak_admin.wa' => '081234567890', 'diskat.kontak_admin.kantor' => 'Kantor Uji']);
        $operator = $this->user(Role::OPERATOR);
        $this->gagal($operator, 3);

        $this->post('/login', ['email' => $operator->email, 'password' => self::PASSWORD])->assertSessionHasErrors('email');

        $pesan = session('errors')->first('email');
        $this->assertStringContainsString('081234567890', $pesan);
        $this->assertStringContainsString('Kantor Uji', $pesan);
    }

    public function test_fitur_dapat_dimatikan(): void
    {
        config(['diskat.kunci_akun.aktif' => false]);
        $operator = $this->user(Role::OPERATOR);

        $this->gagal($operator, 5);
        $this->assertNull($operator->refresh()->terblokir_at);
    }

    public function test_password_admin_minimal_12_karakter(): void
    {
        $admin = $this->user(Role::ADMIN);
        $roleAdmin = Role::where('name', Role::ADMIN)->value('id');

        $data = fn (string $email, string $pw) => [
            'name' => 'Admin Baru', 'email' => $email, 'role_id' => $roleAdmin,
            'password' => $pw, 'password_confirmation' => $pw, 'is_active' => true,
        ];

        $this->actingAs($admin)->post('/users', $data('pendek@contoh.id', 'Abcdef123'))->assertSessionHasErrors('password');
        $this->post('/users', $data('panjang@contoh.id', 'Abcdefgh12345'))->assertSessionHasNoErrors();
        $this->assertDatabaseHas('users', ['email' => 'panjang@contoh.id']);
    }

    public function test_kegagalan_dicatat_di_log_aktivitas(): void
    {
        $operator = $this->user(Role::OPERATOR);
        $this->gagal($operator, 1);

        $this->assertDatabaseHas('activity_logs', ['aksi' => 'auth.login_gagal', 'user_id' => $operator->id]);
    }
}
