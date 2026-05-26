<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = config('auth.bootstrap_admin');

        User::updateOrCreate(
            ['email' => $admin['email']],
            [
                'name' => $admin['name'],
                'role' => 'admin',
                'section' => $admin['section'],
                'approval_status' => 'approved',
                'approved_at' => now(),
                'password' => Hash::make($admin['password']),
                'email_verified_at' => now(),
            ]
        );
    }
}
