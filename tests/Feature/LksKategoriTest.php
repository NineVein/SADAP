<?php

namespace Tests\Feature;

use App\Models\Anak;
use App\Models\Kelurahan;
use App\Models\Panti;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Sistem LKS: satu LKS melayani satu kategori (anak, lanjut usia, disabilitas),
 * dan kategori klien selalu mengikuti kategori LKS.
 */
class LksKategoriTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Kelurahan $kelurahan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->admin = User::factory()->create([
            'role_id' => Role::where('name', Role::ADMIN)->value('id'),
            'is_active' => true,
        ]);

        $this->kelurahan = Kelurahan::create([
            'nama' => 'Kelurahan Contoh', 'kecamatan' => 'Tondano Barat', 'tipe' => 'kelurahan',
        ]);
    }

    private function lks(string $kategori, ?string $nama = null): Panti
    {
        $panti = new Panti([
            'nama' => $nama ?? 'LKS '.$kategori,
            'kategori_lks' => $kategori,
            'alamat' => 'Jl. Contoh',
            'kelurahan_id' => $this->kelurahan->id,
            'status' => 'aktif',
        ]);
        $panti->save();

        return $panti;
    }

    private function dataKlien(Panti $panti, array $override = []): array
    {
        return array_merge([
            'panti_id' => $panti->id,
            'nama' => 'Klien Uji',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => now()->subYears(10)->format('Y-m-d'),
            'kategori' => 'dhuafa',
            'status' => 'aktif',
        ], $override);
    }

    public function test_lks_baru_menyimpan_kategori_yang_dipilih(): void
    {
        $this->actingAs($this->admin)->post(route('panti.store'), [
            'nama' => 'LKS Lansia Sejahtera',
            'kategori_lks' => 'lanjut_usia',
            'alamat' => 'Jl. Sejahtera',
            'kelurahan_id' => $this->kelurahan->id,
            'status' => 'aktif',
        ])->assertRedirect();

        $this->assertDatabaseHas('pantis', ['nama' => 'LKS Lansia Sejahtera', 'kategori_lks' => 'lanjut_usia']);
    }

    public function test_kategori_lks_tidak_valid_ditolak(): void
    {
        $this->actingAs($this->admin)->post(route('panti.store'), [
            'nama' => 'LKS Salah',
            'kategori_lks' => 'hewan',
            'alamat' => 'Jl. Salah',
            'kelurahan_id' => $this->kelurahan->id,
            'status' => 'aktif',
        ])->assertSessionHasErrors('kategori_lks');
    }

    public function test_kategori_lks_terkunci_jika_sudah_punya_klien(): void
    {
        $lks = $this->lks('anak');
        $this->actingAs($this->admin)->post(route('anak.store'), $this->dataKlien($lks))->assertSessionHasNoErrors();

        $this->actingAs($this->admin)->put(route('panti.update', $lks), [
            'nama' => $lks->nama,
            'kategori_lks' => 'disabilitas',
            'alamat' => $lks->alamat,
            'kelurahan_id' => $this->kelurahan->id,
            'status' => 'aktif',
        ])->assertSessionHasErrors('kategori_lks');

        $this->assertDatabaseHas('pantis', ['id' => $lks->id, 'kategori_lks' => 'anak']);
    }

    public function test_kategori_klien_mengikuti_kategori_lks(): void
    {
        $lks = $this->lks('lanjut_usia');

        $this->actingAs($this->admin)->post(route('anak.store'), $this->dataKlien($lks, [
            'nama' => 'Opa Contoh',
            'tanggal_lahir' => now()->subYears(70)->format('Y-m-d'),
            'kategori' => 'sebatang_kara',
            'tingkat_ketergantungan' => 'sebagian',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('anaks', ['nama' => 'Opa Contoh', 'kategori_klien' => 'lanjut_usia']);
    }

    public function test_klien_anak_tidak_boleh_melebihi_batas_usia(): void
    {
        $lks = $this->lks('anak');

        $this->actingAs($this->admin)->post(route('anak.store'), $this->dataKlien($lks, [
            'tanggal_lahir' => now()->subYears(30)->format('Y-m-d'),
        ]))->assertSessionHasErrors('tanggal_lahir');
    }

    public function test_klien_lanjut_usia_harus_memenuhi_usia_minimal(): void
    {
        $lks = $this->lks('lanjut_usia');

        $this->actingAs($this->admin)->post(route('anak.store'), $this->dataKlien($lks, [
            'tanggal_lahir' => now()->subYears(40)->format('Y-m-d'),
            'kategori' => 'terlantar',
            'tingkat_ketergantungan' => 'mandiri',
        ]))->assertSessionHasErrors('tanggal_lahir');
    }

    public function test_klien_disabilitas_wajib_ragam_dan_tingkat_ketergantungan(): void
    {
        $lks = $this->lks('disabilitas');

        $this->actingAs($this->admin)->post(route('anak.store'), $this->dataKlien($lks, [
            'tanggal_lahir' => now()->subYears(35)->format('Y-m-d'),
        ]))->assertSessionHasErrors(['jenis_disabilitas', 'tingkat_ketergantungan']);

        $this->actingAs($this->admin)->post(route('anak.store'), $this->dataKlien($lks, [
            'nama' => 'Klien Disabilitas Uji',
            'tanggal_lahir' => now()->subYears(35)->format('Y-m-d'),
            'jenis_disabilitas' => 'fisik',
            'tingkat_ketergantungan' => 'mandiri',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('anaks', ['nama' => 'Klien Disabilitas Uji', 'kategori_klien' => 'disabilitas']);
    }

    public function test_latar_belakang_harus_sesuai_kategori(): void
    {
        $lks = $this->lks('lanjut_usia');

        // "yatim" hanya berlaku untuk kategori Anak.
        $this->actingAs($this->admin)->post(route('anak.store'), $this->dataKlien($lks, [
            'tanggal_lahir' => now()->subYears(70)->format('Y-m-d'),
            'kategori' => 'yatim',
            'tingkat_ketergantungan' => 'mandiri',
        ]))->assertSessionHasErrors('kategori');
    }

    public function test_klien_tidak_bisa_dipindah_ke_lks_kategori_lain(): void
    {
        $lksAnak = $this->lks('anak', 'LKS Anak A');
        $lksLansia = $this->lks('lanjut_usia', 'LKS Lansia B');

        $this->actingAs($this->admin)->post(route('anak.store'), $this->dataKlien($lksAnak))->assertSessionHasNoErrors();
        $klien = Anak::where('nama', 'Klien Uji')->firstOrFail();

        $this->actingAs($this->admin)->put(route('anak.update', $klien), $this->dataKlien($lksLansia))
            ->assertSessionHasErrors('panti_id');
    }

    public function test_daftar_lks_dapat_difilter_per_kategori(): void
    {
        $this->lks('anak', 'LKS Anak X');
        $this->lks('disabilitas', 'LKS Disabilitas Y');

        $this->actingAs($this->admin)->get('/lks?kategori=disabilitas')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Panti/Index')
                ->where('pantis.total', 1)
                ->where('pantis.data.0.nama', 'LKS Disabilitas Y')
                ->where('kategoriCounts.semua', 2));
    }

    public function test_alamat_lama_diarahkan_ke_alamat_baru(): void
    {
        $this->actingAs($this->admin)->get('/panti?kategori=anak')->assertRedirect('/lks?kategori=anak');
        $this->actingAs($this->admin)->get('/anak')->assertRedirect('/klien');
    }
}
