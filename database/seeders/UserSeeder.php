<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // [nik, fullName, phone, email, isAdmin, isApproved]
        $users = [
            ['3374000000000001', 'Admin', '081200000001', 'admin@garagerent.com', true, true],
            ['3374000000000002', 'Budi Santoso', '081200000002', 'budi@garagerent.com', false, true],
            ['3374000000000003', 'Siti Rahma', '081200000003', 'siti@garagerent.com', false, true],
            ['3374000000000004', 'Andi Wijaya', '081200000004', 'andi@garagerent.com', false, true],
            ['3374000000000005', 'Dewi Lestari', '081200000005', 'dewi@garagerent.com', false, true],
            ['3374000000000006', 'Rudi Hartono', '081200000006', 'rudi@garagerent.com', false, true],
            ['3374000000000007', 'user', '081200000007', 'a@a.com', false, true],
            // not approved yet, shows up in Review Users
            ['3374000000000008', 'Fajar Pratama', '081200000008', 'fajar@garagerent.com', false, false],
        ];

        foreach ($users as [$nik, $fullName, $phone, $email, $isAdmin, $isApproved]) {
            User::forceCreate([
                'nik' => $nik,
                'fullName' => $fullName,
                'phone' => $phone,
                'email' => $email,
                'password' => Hash::make('password'),
                'isAdmin' => $isAdmin,
                'isApproved' => $isApproved,
            ]);
        }
    }
}