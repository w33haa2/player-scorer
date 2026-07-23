<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed the tournament admin users.
     */
    public function run(): void
    {
        $admins = [
            ['name' => 'Admin One', 'email' => 'admin1@dbbl.com'],
            ['name' => 'Admin Two', 'email' => 'admin2@dbbl.com'],
        ];

        foreach ($admins as $admin) {
            User::firstOrCreate(
                ['email' => $admin['email']],
                [
                    'name' => $admin['name'],
                    'password' => Hash::make('dbbl123!'),
                    'email_verified_at' => now(),
                ],
            );
        }
    }
}
