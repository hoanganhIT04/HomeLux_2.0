<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'id' => 1,
                'name' => 'hoanganh',
                'email' => 'anhemhucau@gmail.com',
                'email_verified_at' => '2026-01-26 14:52:35',
                'verification_code' => null,
                'password' => '$2y$12$FDsF49nKRpwFH3zMDQfqueSUWVqFd.cFCNLYMgKPyrsOjDaBUuFnO',
                'role' => 'user',
                'created_at' => '2026-01-25 23:37:51',
                'updated_at' => '2026-01-25 23:38:22',
            ],
            [
                'id' => 2,
                'name' => 'âu mạnh',
                'email' => 'aumanh11112004@gmail.com',
                'email_verified_at' => '2026-01-26 14:52:36',
                'verification_code' => null,
                'password' => '$2y$12$m5LYOGVTomsL1BjhonRkR.gnfIUNrMzm1UC5LkQ9nhN46mq4lCU4u',
                'role' => 'user',
                'created_at' => '2026-01-25 23:42:42',
                'updated_at' => '2026-01-25 23:43:09',
            ],
            [
                'id' => 3,
                'name' => 'Admin',
                'email' => 'admin@toyshop.com',
                'email_verified_at' => '2026-01-27 15:15:49',
                'verification_code' => null,
                'password' => '$2y$12$u3/AnzyhQg3aFxJ/jgO8wO3.t0kgkOoT3FPWwv/25F6jGK8r2LzY2',
                'role' => 'admin',
                'created_at' => '2026-01-27 08:15:09',
                'updated_at' => '2026-01-27 08:15:09',
            ]
        ]);
    }
}
