<?php

namespace Tests\Feature;

use App\Models\Kelurahan;
use App\Models\Panti;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PantiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * Admin Dinas Sosial dapat menambahkan data panti.
     */
    public function test_admin_can_create_panti(): void
    {
        $adminRole = Role::where('name', Role::ADMIN)->first();
        $kelurahan = Kelurahan::create([
            'nama' => 'Kelurahan Wewelen',
            'kecamatan' => 'Tondano Barat',
            'tipe' => 'kelurahan',
        ]);

        $user = User::factory()->create([
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(
            route('panti.store'),
            [
                'nama' => 'Panti Asuhan Kasih',
                'alamat' => 'Jl. Kasih No. 12',
                'kelurahan_id' => $kelurahan->id,
                'email' => 'kasih@example.test',
                'status' => 'aktif',
            ]
        );

        $response->assertRedirect();
        $this->assertDatabaseHas('pantis', [
            'nama' => 'Panti Asuhan Kasih',
            'alamat' => 'Jl. Kasih No. 12',
            'kelurahan_id' => $kelurahan->id,
            'status' => 'aktif',
        ]);
    }

    /**
     * Validasi panti membutuhkan nama, alamat, status, dan kelurahan.
     */
    public function test_panti_creation_requires_mandatory_fields(): void
    {
        $adminRole = Role::where('name', Role::ADMIN)->first();

        $user = User::factory()->create([
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(
            route('panti.store'),
            [
                'nama' => '',
                'alamat' => '',
                'kelurahan_id' => '',
                'status' => '',
            ]
        );

        $response->assertSessionHasErrors(['nama', 'alamat', 'kelurahan_id', 'status']);
        $this->assertDatabaseCount('pantis', 0);
    }

    /**
     * Operator tanpa kelurahan tidak dapat menambahkan panti.
     */
    public function test_operator_without_kelurahan_cannot_create_panti(): void
    {
        $operatorRole = Role::where('name', Role::OPERATOR)->first();

        $user = User::factory()->create([
            'role_id' => $operatorRole->id,
            'kelurahan_id' => null,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->post(
            route('panti.store'),
            [
                'nama' => 'Panti Tak Berizin',
                'alamat' => 'Jl. Melati',
                'status' => 'aktif',
            ]
        );

        $response->assertForbidden();
        $this->assertDatabaseMissing('pantis', [
            'nama' => 'Panti Tak Berizin',
        ]);
    }

    /**
     * Pengguna terautentikasi dan aktif dapat melihat daftar panti.
     */
    public function test_authenticated_user_can_view_panti_list(): void
    {
        $adminRole = Role::where('name', Role::ADMIN)->first();
        $kelurahan = Kelurahan::create([
            'nama' => 'Kelurahan Rerewokan',
            'kecamatan' => 'Tondano Barat',
            'tipe' => 'kelurahan',
        ]);

        $user = User::factory()->create([
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);

        Panti::create([
            'nama' => 'Panti Asuhan Minahasa',
            'alamat' => 'Tondano Barat',
            'kelurahan_id' => $kelurahan->id,
            'status' => 'aktif',
        ]);

        $response = $this->actingAs($user)->get(route('panti.index'));

        $response->assertOk();
    }
}