<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Garage;
use Illuminate\Database\Seeder;

class GarageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $prefixes = [
            'bicycle' => 'S',
            'car' => 'C',
            'electric car' => 'E',
        ];

        // [type, amount, rentPrice, electricPrice]
        $batches = [
            ['bicycle', 50, 5000, 0],
            ['bicycle', 30, 6000, 0],
            ['bicycle', 20, 7500, 0],

            ['car', 100, 20000, 2000],
            ['car', 50, 25000, 2500],
            ['car', 40, 30000, 3000],
            ['car', 10, 35000, 3500],

            ['electric car', 40, 40000, 2500],
            ['electric car', 30, 45000, 3000],
            ['electric car', 20, 50000, 3500],
        ];

        $counters = ['bicycle' => 0, 'car' => 0, 'electric car' => 0];
        $rows = [];

        foreach ($batches as [$type, $amount, $rentPrice, $electricPrice]) {
            for ($i = 0; $i < $amount; $i++) {
                $counters[$type]++;

                $rows[] = [
                    'id' => $prefixes[$type] . str_pad($counters[$type], 3, '0', STR_PAD_LEFT),
                    'garageType' => $type,
                    'rentPrice' => $rentPrice,
                    'electricPrice' => $electricPrice,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        Garage::insert($rows);
    }
}