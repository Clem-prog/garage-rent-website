<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::forceCreate([
            'nik' => '3374000000000001',
            'fullName' => 'Admin',
            'phone' => '081200000001',
            'email' => 'admin@garagerent.com',
            'password' => Hash::make('password'),
            'isAdmin' => true,
            'isApproved' => true,
        ]);

        User::forceCreate([
            'nik' => '3374000000000002',
            'fullName' => 'Test User',
            'phone' => '081200000002',
            'email' => 'user@garagerent.com',
            'password' => Hash::make('password'),
            'isAdmin' => false,
            'isApproved' => true,
        ]);
    }
}