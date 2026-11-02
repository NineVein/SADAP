<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            Role::ADMIN => 'Administrator Dinas Sosial',
            Role::SUPERVISOR => 'Supervisor Dinas Sosial',
            Role::OPERATOR => 'Operator Kelurahan/Desa',
        ];

        foreach ($roles as $name => $label) {
            Role::updateOrCreate(['name' => $name], ['label' => $label]);
        }
    }
}
