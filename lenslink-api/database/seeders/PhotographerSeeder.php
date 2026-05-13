<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class PhotographerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $photographers = [
            [
                'name'        => 'John Vision',
                'email'       => 'john@example.com',
                'password'    => Hash::make('password'),
                'role_id'     => 2,
                'specialty'   => 'Wedding & Events',
                'bio'         => 'Capturing your special moments with a cinematic touch.',
                'location'    => 'New York, NY',
                'price_range' => '$$$',
            ],
            [
                'name'        => 'Sarah Lens',
                'email'       => 'sarah@example.com',
                'password'    => Hash::make('password'),
                'role_id'     => 2,
                'specialty'   => 'Portrait & Fashion',
                'bio'         => 'Fashion forward portraits for the modern era.',
                'location'    => 'Los Angeles, CA',
                'price_range' => '$$$$',
            ],
            [
                'name'        => 'Mike Snap',
                'email'       => 'mike@example.com',
                'password'    => Hash::make('password'),
                'role_id'     => 2,
                'specialty'   => 'Landscape & Nature',
                'bio'         => 'Bringing the beauty of nature to your living room.',
                'location'    => 'Denver, CO',
                'price_range' => '$$',
            ],
        ];

        foreach ($photographers as $p) {
            User::updateOrCreate(['email' => $p['email']], $p);
        }

        $this->command->info('Seeded 3 photographers.');
    }
}
