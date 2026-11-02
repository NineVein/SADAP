<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use App\Models\JenisBantuan;
use App\Models\Kelurahan;
use App\Models\Panti;
use App\Models\Pengurus;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\JenisBantuanSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PantiProfilTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Panti $panti;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(JenisBantuanSeeder::class);

        $adminRole = Role::where('name', Role::ADMIN)->first();
        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $kelurahan = Kelurahan::create([
            'nama' => 'Kelurahan Rinegetan',
            'kecamatan' => 'Tondano Barat',
            'tipe' => 'kelurahan',
        ]);

        $this->panti = Panti::create([
            'nama' => 'Panti Asuhan Melati',
            'alamat' => 'Jl. Melati No. 5',
            'kelurahan_id' => $kelurahan->id,
            'status' => 'aktif',
        ]);
    }

    public function test_can_add_pengurus_with_periode_and_status(): void
    {
        $response = $this->actingAs($this->admin)->post(route('pengurus.store'), [
            'panti_id' => $this->panti->id,
            'nama' => 'Dra. Maria Sondakh',
            'jabatan' => 'Ketua Pengurus',
            'telepon' => '081234567890',
            'email' => 'maria@example.com',
            'periode_mulai' => '2024-01-01',
            'periode_selesai' => '2027-01-01',
            'status' => 'aktif',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pengurus', [
            'panti_id' => $this->panti->id,
            'nama' => 'Dra. Maria Sondakh',
            'jabatan' => 'Ketua Pengurus',
            'periode_mulai' => '2024-01-01',
            'periode_selesai' => '2027-01-01',
            'status' => 'aktif',
        ]);
    }

    public function test_can_update_pengurus_status_and_periode(): void
    {
        $pengurus = Pengurus::create([
            'panti_id' => $this->panti->id,
            'nama' => 'Budi Santoso',
            'jabatan' => 'Sekretaris',
            'telepon' => '081234567891',
            'periode_mulai' => '2022-01-01',
            'periode_selesai' => '2025-01-01',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($this->admin)->put(route('pengurus.update', $pengurus), [
            'panti_id' => $this->panti->id,
            'nama' => 'Budi Santoso, S.Pd',
            'jabatan' => 'Sekretaris',
            'telepon' => '081234567891',
            'periode_mulai' => '2022-01-01',
            'periode_selesai' => '2025-01-01',
            'status' => 'nonaktif',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('pengurus', [
            'id' => $pengurus->id,
            'nama' => 'Budi Santoso, S.Pd',
            'status' => 'nonaktif',
        ]);
    }

    public function test_pengurus_jabatan_tunggal_prevents_duplicate_active_core_role(): void
    {
        // Ketua 1 aktif
        Pengurus::create([
            'panti_id' => $this->panti->id,
            'nama' => 'Ketua Pertama',
            'jabatan' => 'Ketua',
            'status' => 'aktif',
        ]);

        // Mencoba tambah Ketua kedua yang juga aktif di panti yang sama
        $response = $this->actingAs($this->admin)->post(route('pengurus.store'), [
            'panti_id' => $this->panti->id,
            'nama' => 'Ketua Kedua',
            'jabatan' => 'Ketua',
            'status' => 'aktif',
        ]);

        $response->assertSessionHasErrors(['jabatan']);
    }

    public function test_dokumen_stores_nomor_and_berlaku_sampai_and_calculates_kadaluarsa(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('izin_operasional.pdf', 500, 'application/pdf');

        $response = $this->actingAs($this->admin)->post(route('dokumen.store'), [
            'panti_id' => $this->panti->id,
            'jenis' => 'izin_operasional',
            'judul' => 'Surat Izin Operasional 2024',
            'nomor' => '503/IO/DINSOS/2024',
            'berlaku_sampai' => '2024-01-01', // tanggal lampau (kedaluwarsa)
            'file' => $file,
        ]);

        $response->assertRedirect();

        $dokumen = Dokumen::where('panti_id', $this->panti->id)->first();
        $this->assertNotNull($dokumen);
        $this->assertEquals('503/IO/DINSOS/2024', $dokumen->nomor);
        $this->assertEquals('2024-01-01', $dokumen->berlaku_sampai->toDateString());
        $this->assertTrue($dokumen->kadaluarsa);

        // Dokumen yang belum kedaluwarsa
        $file2 = UploadedFile::fake()->create('akta_pendirian.pdf', 500, 'application/pdf');
        $this->actingAs($this->admin)->post(route('dokumen.store'), [
            'panti_id' => $this->panti->id,
            'jenis' => 'akta_pendirian',
            'judul' => 'Akta Pendirian Notaris',
            'nomor' => 'AHU-00123.AH.01.04.2024',
            'berlaku_sampai' => now()->addYears(2)->toDateString(),
            'file' => $file2,
        ]);

        $dokumen2 = Dokumen::where('jenis', 'akta_pendirian')->first();
        $this->assertNotNull($dokumen2);
        $this->assertFalse($dokumen2->kadaluarsa);
    }

    public function test_can_save_and_sync_jenis_bantuan(): void
    {
        $sembako = JenisBantuan::where('kode', 'sembako')->first();
        $pendidikan = JenisBantuan::where('kode', 'biaya_pendidikan')->first();

        $response = $this->actingAs($this->admin)->put(route('panti.jenis-bantuan', $this->panti), [
            'jenis_bantuan' => [
                [
                    'id' => $sembako->id,
                    'catatan' => 'Kebutuhan beras 50kg per bulan',
                ],
                [
                    'id' => $pendidikan->id,
                    'catatan' => 'Buku dan seragam untuk 15 anak',
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('panti_jenis_bantuan', [
            'panti_id' => $this->panti->id,
            'jenis_bantuan_id' => $sembako->id,
            'catatan' => 'Kebutuhan beras 50kg per bulan',
        ]);

        $this->assertDatabaseHas('panti_jenis_bantuan', [
            'panti_id' => $this->panti->id,
            'jenis_bantuan_id' => $pendidikan->id,
            'catatan' => 'Buku dan seragam untuk 15 anak',
        ]);

        // Cek update / sync hanya 1 jenis bantuan
        $this->actingAs($this->admin)->put(route('panti.jenis-bantuan', $this->panti), [
            'jenis_bantuan' => [
                [
                    'id' => $sembako->id,
                    'catatan' => 'Kebutuhan beras dinaikkan jadi 75kg',
                ],
            ],
        ]);

        $this->assertDatabaseHas('panti_jenis_bantuan', [
            'panti_id' => $this->panti->id,
            'jenis_bantuan_id' => $sembako->id,
            'catatan' => 'Kebutuhan beras dinaikkan jadi 75kg',
        ]);

        $this->assertDatabaseMissing('panti_jenis_bantuan', [
            'panti_id' => $this->panti->id,
            'jenis_bantuan_id' => $pendidikan->id,
        ]);
    }

    public function test_panti_checklist_includes_new_indicators(): void
    {
        $checklist = collect($this->panti->checklist())->keyBy('key');

        $this->assertTrue($checklist->has('dokumen'));
        $this->assertTrue($checklist->has('jenis_bantuan'));
        $this->assertFalse($checklist['dokumen']['done']);
        $this->assertFalse($checklist['jenis_bantuan']['done']);

        // Tambah jenis bantuan
        $sembako = JenisBantuan::first();
        $this->panti->jenisBantuan()->attach($sembako->id);

        $checklistAfterBantuan = collect($this->panti->fresh()->checklist())->keyBy('key');
        $this->assertTrue($checklistAfterBantuan['jenis_bantuan']['done']);
    }

    public function test_security_headers_are_present(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }

    public function test_dokumen_download_has_security_headers(): void
    {
        Storage::fake('local');

        $file = UploadedFile::fake()->create('dokumen_legalitas.pdf', 200, 'application/pdf');
        $this->actingAs($this->admin)->post(route('dokumen.store'), [
            'panti_id' => $this->panti->id,
            'jenis' => 'npwp',
            'judul' => 'NPWP Lembaga',
            'file' => $file,
        ]);

        $dokumen = Dokumen::where('panti_id', $this->panti->id)->first();
        $this->assertNotNull($dokumen);

        $downloadResponse = $this->actingAs($this->admin)->get(route('dokumen.download', $dokumen));
        $downloadResponse->assertOk();
        $downloadResponse->assertHeader('X-Content-Type-Options', 'nosniff');
    }
}
