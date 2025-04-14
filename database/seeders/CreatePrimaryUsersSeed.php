<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CreatePrimaryUsersSeed extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'id' => 1,
                'first_name' => 'Super',
                'last_name' => 'Administrator',
                'username' => 'super_admin',
                'password' => Hash::make("Project@1234"),
                'email' => 'superadmin@gmail.com',
                'role_id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
                'verify_email' => "1",
            ],
            [
                'id' => 2,
                'first_name' => 'Admin',
                'last_name' => '',
                'username' => 'admin',
                'password' => Hash::make("Project@1234"),
                'email' => 'admin@gmail.com',
                'role_id' => 2,
                'account_tokens' => 100000,
                'created_at' => now(),
                'updated_at' => now(),
                'verify_email' => "1",
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
