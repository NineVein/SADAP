<?php

namespace Tests\Feature;

use App\Models\Anak;
use App\Models\DesilLaporan;
use App\Models\Kelurahan;
use App\Models\Panti;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesilTest extends TestCase
{
    use RefreshDatabase;

    private Kelurahan $kelurahan;
    private Panti $panti;
    private Anak $anak;
    private User $admin;
    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $this->kelurahan = Kelurahan::create([
            'nama' => 'Wewelen',
            'kecamatan' => 'Tondano Barat',
            'tipe' => 'kelurahan',
        ]);

        $adminRole = Role::where('name', Role::ADMIN)->first();
        $operatorRole = Role::where('name', Role::OPERATOR)->first();

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
            'nama' => 'Panti Wewelen Kasih',
            'alamat' => 'Jl. Wewelen',
            'kelurahan_id' => $this->kelurahan->id,
            'status' => 'aktif',
            'created_by' => $this->admin->id,
        ]);

        $this->anak = Anak::create([
            'panti_id' => $this->panti->id,
            'nama' => 'Budi Santoso',
            'jenis_kelamin' => 'L',
            'tanggal_lahir' => '2015-01-01',
            'kategori' => 'yatim',
            'status' => 'aktif',
        ]);
    }

    public function test_operator_can_submit_desil_report_as_pending(): void
    {
        $response = $this->actingAs($this->operator)->post(route('desil.store', $this->anak), [
            'desil' => 2,
            'sumber' => 'dtks',
            'tanggal_data' => '2026-01-10',
            'catatan' => 'Sesuai data bansos',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('desil_laporans', [
            'anak_id' => $this->anak->id,
            'desil' => 2,
            'sumber' => 'dtks',
            'status' => DesilLaporan::MENUNGGU,
            'dilaporkan_by' => $this->operator->id,
        ]);

        // Desil di tabel anaks belum berubah
        $this->assertNull($this->anak->fresh()->desil);
    }

    public function test_admin_desil_submission_is_directly_approved(): void
    {
        $response = $this->actingAs($this->admin)->post(route('desil.store', $this->anak), [
            'desil' => 1,
            'sumber' => 'dtsen',
            'catatan' => 'Langsung valid oleh dinsos',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('desil_laporans', [
            'anak_id' => $this->anak->id,
            'desil' => 1,
            'status' => DesilLaporan::DISETUJUI,
            'dilaporkan_by' => $this->admin->id,
        ]);

        // Desil anak langsung terisi
        $this->assertEquals(1, $this->anak->fresh()->desil);
    }

    public function test_reviewer_can_approve_pending_desil_report(): void
    {
        $laporan = DesilLaporan::create([
            'anak_id' => $this->anak->id,
            'desil' => 3,
            'sumber' => 'survei',
            'status' => DesilLaporan::MENUNGGU,
            'dilaporkan_by' => $this->operator->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('desil.setujui', $laporan));

        $response->assertSessionHas('success');
        $this->assertEquals(DesilLaporan::DISETUJUI, $laporan->fresh()->status);
        $this->assertEquals(3, $this->anak->fresh()->desil);
    }

    public function test_reviewer_can_reject_pending_desil_report_with_notes(): void
    {
        $laporan = DesilLaporan::create([
            'anak_id' => $this->anak->id,
            'desil' => 3,
            'sumber' => 'survei',
            'status' => DesilLaporan::MENUNGGU,
            'dilaporkan_by' => $this->operator->id,
        ]);

        $response = $this->actingAs($this->admin)->post(route('desil.tolak', $laporan), [
            'catatan' => 'Bukti survei lapangan belum dilampirkan',
        ]);

        $response->assertSessionHas('success');
        $this->assertEquals(DesilLaporan::DITOLAK, $laporan->fresh()->status);
        $this->assertEquals('Bukti survei lapangan belum dilampirkan', $laporan->fresh()->catatan_review);
        $this->assertNull($this->anak->fresh()->desil);
    }
}

