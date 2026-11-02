<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Anak;
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
use Tests\TestCase;

class DinasStructureIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $operator;
    protected Panti $panti;
    protected Kelurahan $kelurahan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(JenisBantuanSeeder::class);

        $adminRole = Role::where('name', Role::ADMIN)->first();
        $operatorRole = Role::where('name', Role::OPERATOR)->first();

        $this->kelurahan = Kelurahan::create([
            'nama' => 'Rinegetan',
            'kecamatan' => 'Tondano Barat',
            'tipe' => 'kelurahan',
        ]);

        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $this->operator = User::factory()->create([
            'role_id' => $operatorRole->id,
            'kelurahan_id' => $this->kelurahan->id,
            'is_active' => true,
        ]);

        $this->panti = Panti::create([
            'nama' => 'Panti Asuhan Kasih Ibu',
            'alamat' => 'Jl. Kasih No. 10',
            'kelurahan_id' => $this->kelurahan->id,
            'status' => 'aktif',
            'validation_status' => 'draft',
        ]);
    }

    public function test_api_referensi_returns_valid_data(): void
    {
        $response = $this->getJson('/api/referensi');

        $response->assertOk();
        $response->assertJsonStructure([
            'kategori_dokumen_pengajuan',
            'kategori_dokumen_instansi',
            'jenis_bantuan',
            'sumber_dana',
            'kategori_anak',
            'jabatan',
        ]);
    }

    public function test_api_dashboard_returns_summary(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/dashboard');

        $response->assertOk();
        $response->assertJsonStructure([
            'tahun',
            'ringkasan' => [
                'total_panti',
                'total_anak_asuh',
                'panti_menunggu_validasi',
                'nilai_bantuan_tersalurkan',
            ],
            'perlu_tindakan',
            'grafik_bulanan',
            'pengajuan_terbaru',
        ]);
    }

    public function test_api_panti_list_and_detail(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/panti');
        $response->assertOk();

        $detail = $this->actingAs($this->admin)->getJson("/api/panti/{$this->panti->id}");
        $detail->assertOk();
        $detail->assertJsonFragment([
            'id' => $this->panti->id,
            'nama' => 'Panti Asuhan Kasih Ibu',
        ]);
    }

    public function test_panti_profil_aktivitas_api_and_web_routes(): void
    {
        // Catat aktivitas perubahan
        ActivityLog::record('panti.diubah', "Memperbarui alamat panti", $this->panti, $this->admin->id);

        $pengurus = Pengurus::create([
            'panti_id' => $this->panti->id,
            'nama' => 'Dra. Maria Sondakh',
            'jabatan' => 'Ketua',
            'status' => 'aktif',
        ]);
        ActivityLog::record('pengurus.dibuat', "Menambahkan pengurus Dra. Maria Sondakh", $pengurus, $this->admin->id);

        // API route: /api/panti/{id}/profil/aktivitas
        $apiResponse = $this->actingAs($this->admin)->getJson("/api/panti/{$this->panti->id}/profil/aktivitas");
        $apiResponse->assertOk();
        $apiResponse->assertJsonStructure(['data']);
        $this->assertCount(2, $apiResponse->json('data'));

        // Web route: /panti/{id}/profil/aktivitas
        $webResponse = $this->actingAs($this->admin)->getJson("/panti/{$this->panti->id}/profil/aktivitas");
        $webResponse->assertOk();
        $this->assertCount(2, $webResponse->json('data'));
    }

    public function test_panti_show_inertia_renders_with_activity_logs_and_tabs(): void
    {
        ActivityLog::record('panti.dibuat', "Pendaftaran panti baru", $this->panti, $this->admin->id);

        $response = $this->actingAs($this->admin)->get(route('panti.show', [
            'panti' => $this->panti,
            'tab' => 'aktivitas',
        ]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Panti/Show')
            ->has('panti')
            ->has('anaks')
            ->has('pengurus')
            ->has('dokumens')
            ->has('jenisBantuan')
            ->has('activityLogs', 1)
            ->where('tab', 'aktivitas')
        );
    }

    public function test_api_anak_binaan_list(): void
    {
        Anak::create([
            'panti_id' => $this->panti->id,
            'nama' => 'Michael Kolondam',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2015-05-10',
            'kategori' => 'yatim',
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($this->admin)->getJson("/api/anak-binaan?panti_id={$this->panti->id}");
        $response->assertOk();
        $this->assertEquals(1, $response->json('total'));
    }

    public function test_api_monitoring_and_riwayat(): void
    {
        $mon = $this->actingAs($this->admin)->getJson('/api/monitoring');
        $mon->assertOk();
        $mon->assertJsonStructure(['tahun', 'corong', 'per_kecamatan']);

        $riwayat = $this->actingAs($this->admin)->getJson('/api/riwayat-bantuan');
        $riwayat->assertOk();
    }
}
