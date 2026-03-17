<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Core\Database\Seeders\RolesAndPermissionsSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Prima i ruoli e permessi
        $this->call(RolesAndPermissionsSeeder::class);

        // Super Admin di default
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@ispmanager.local'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('IspManager@2024!'),
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $superAdmin->assignRole('super-admin');

        $this->command->info('Super admin creato: admin@ispmanager.local');
        $this->command->warn('Cambia la password di default immediatamente!');

        // Template email di default
        $this->call(EmailTemplatesSeeder::class);
    }
}
