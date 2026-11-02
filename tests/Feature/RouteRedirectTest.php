<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RouteRedirectTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        $adminRole = Role::where('name', Role::ADMIN)->first();
        $this->admin = User::factory()->create([
            'role_id' => $adminRole->id,
            'is_active' => true,
        ]);
    }

    public function test_legacy_routes_redirect_properly(): void
    {
        $this->actingAs($this->admin)->get('/pengurus')->assertRedirect('/panti');
        $this->actingAs($this->admin)->get('/dokumen')->assertRedirect('/panti');
        $this->actingAs($this->admin)->get('/validasi')->assertRedirect(route('panti.index', ['validasi' => 'diajukan']));
        $this->actingAs($this->admin)->get('/desil')->assertRedirect(route('anak.index', ['desil' => 'belum']));
    }
}

