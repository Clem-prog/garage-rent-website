<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class VehicleSeeder extends Seeder
{
    public function run(): void
    {
        // [email, type, plateNum, brand, battCapacity, battMaximum]
        $vehicles = [
            ['budi@garagerent.com', 'bicycle', null, 'Polygon Heist', null, null],
            ['budi@garagerent.com', 'motorcycle', 'H 1234 AB', 'Honda Beat', null, null],

            ['siti@garagerent.com', 'car', 'H 2345 CD', 'Toyota Avanza', null, null],
            ['siti@garagerent.com', 'bicycle', null, 'United Miami', null, null],

            ['andi@garagerent.com', 'electric car', 'H 3456 EF', 'Hyundai Ioniq 5', 20, 72.6],
            ['andi@garagerent.com', 'car', 'H 4567 GH', 'Honda Brio', null, null],

            ['dewi@garagerent.com', 'electric car', 'H 5678 IJ', 'Wuling Air EV', 8, 26.7],
            ['dewi@garagerent.com', 'motorcycle', 'H 6789 KL', 'Yamaha NMAX', null, null],

            ['rudi@garagerent.com', 'car', 'H 7890 MN', 'Suzuki Ertiga', null, null],
        ];

        foreach ($vehicles as [$email, $type, $plateNum, $brand, $battCapacity, $battMaximum]) {
            Vehicle::create([
                'user_id' => User::where('email', $email)->value('id'),
                'type' => $type,
                'plateNum' => $plateNum,
                'brand' => $brand,
                'battCapacity' => $battCapacity,
                'battMaximum' => $battMaximum,
            ]);
        }
    }
}