<?php

namespace Tests\Feature;

use App\Models\Anak;
use App\Models\Kelurahan;
use App\Models\Panti;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** NIK klien disimpan terenkripsi; pencarian dan cek duplikat berjalan lewat hash. */
class NikEnkripsiTest extends TestCase
{
    use RefreshDatabase;

    private const NIK = '7102010101120001';

    private User $admin;

    private Panti $lks;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create([
            'role_id' => Role::where('name', Role::ADMIN)->value('id'),
            'is_active' => true,
        ]);

        $kel = Kelurahan::create(['nama' => 'Kelurahan Contoh', 'kecamatan' => 'Tondano Barat', 'tipe' => 'kelurahan']);
        $this->lks = new Panti(['nama' => 'LKS Uji', 'kategori_lks' => 'anak', 'alamat' => 'Jl. Uji', 'kelurahan_id' => $kel->id, 'status' => 'aktif']);
        $this->lks->save();
    }

    private function data(array $override = []): array
    {
        return array_merge([
            'panti_id' => $this->lks->id,
            'nama' => 'Klien Uji',
            'nik' => self::NIK,
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => now()->subYears(10)->format('Y-m-d'),
            'kategori' => 'dhuafa',
            'status' => 'aktif',
        ], $override);
    }

    public function test_nik_tersimpan_terenkripsi_tetapi_terbaca_lewat_model(): void
    {
        $this->actingAs($this->admin)->post(route('anak.store'), $this->data())->assertSessionHasNoErrors();

        $mentah = (string) DB::table('anaks')->value('nik');
        $this->assertNotSame(self::NIK, $mentah);
        $this->assertStringNotContainsString(self::NIK, $mentah);

        $anak = Anak::firstOrFail();
        $this->assertSame(self::NIK, $anak->nik);
        $this->assertSame(Anak::hashNik(self::NIK), DB::table('anaks')->value('nik_hash'));
    }

    public function test_nik_ganda_ditolak(): void
    {
        $this->actingAs($this->admin)->post(route('anak.store'), $this->data())->assertSessionHasNoErrors();

        $this->post(route('anak.store'), $this->data(['nama' => 'Klien Lain']))->assertSessionHasErrors('nik');
        $this->assertSame(1, Anak::count());
    }

    public function test_nik_sendiri_tidak_dianggap_ganda_saat_edit(): void
    {
        $this->actingAs($this->admin)->post(route('anak.store'), $this->data());
        $anak = Anak::firstOrFail();

        $this->put(route('anak.update', $anak), $this->data(['nama' => 'Nama Diubah']))->assertSessionHasNoErrors();
        $this->assertSame('Nama Diubah', $anak->refresh()->nama);
    }

    public function test_pencarian_nik_hanya_untuk_16_digit_lengkap(): void
    {
        $this->actingAs($this->admin)->post(route('anak.store'), $this->data());

        $this->get('/klien?search='.self::NIK)->assertInertia(fn ($page) => $page->where('anaks.total', 1));
        $this->get('/klien?search=71020101')->assertInertia(fn ($page) => $page->where('anaks.total', 0));
    }

    public function test_daftar_hanya_mengirim_nik_tersamar(): void
    {
        $this->actingAs($this->admin)->post(route('anak.store'), $this->data());

        $this->get('/klien')->assertInertia(fn ($page) => $page
            ->where('anaks.data.0.nik_tersamar', '7102••••••••0001')
            ->missing('anaks.data.0.nik'));
    }

    public function test_hash_nik_tidak_ikut_diserialisasi(): void
    {
        $this->actingAs($this->admin)->post(route('anak.store'), $this->data());

        $this->assertArrayNotHasKey('nik_hash', Anak::firstOrFail()->toArray());
    }

    public function test_hash_dan_penyamaran_nik(): void
    {
        $this->assertSame(Anak::hashNik('7102 0101 0112 0001'), Anak::hashNik(self::NIK));
        $this->assertNull(Anak::hashNik(''));
        $this->assertNull(Anak::hashNik(null));
        $this->assertSame('7102••••••••0001', Anak::samarkanNik(self::NIK));
        $this->assertNull(Anak::samarkanNik(null));
    }
}
