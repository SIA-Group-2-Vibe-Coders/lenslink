<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed default roles
        \DB::table('roles')->insertOrIgnore([
            ['id' => 1, 'role_name' => 'Admin', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'role_name' => 'Photographer', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 3, 'role_name' => 'Client', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Create a default admin if not exists
        User::updateOrCreate(
            ['email' => 'admin@lenslink.com'],
            [
                'name' => 'System Admin',
                'password' => \Hash::make('admin123'),
                'role_id' => 1
            ]
        );
    }
}
