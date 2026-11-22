<?php

namespace Database\Seeders;

use App\Models\Garage;
use App\Models\Rent;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class RentSeeder extends Seeder
{
    // which garage types each vehicle type may use
    private array $garageTypes = [
        'bicycle' => ['bicycle'],
        'motorcycle' => ['bicycle'],
        'car' => ['car'],
        'electric car' => ['car', 'electric car'],
    ];

    public function run(): void
    {
        mt_srand(42); // same data on every run

        $vehicles = Vehicle::all()->values();
        $garagesByType = Garage::all()->groupBy('garageType');
        $today = today();

        $booked = []; // garage id => list of [start, end]
        $rows = [];

        for ($i = 0; $i < 60; $i++) {
            $vehicle = $vehicles[mt_rand(0, $vehicles->count() - 1)];

            $garages = collect($this->garageTypes[$vehicle->type])
                ->flatMap(fn ($type) => $garagesByType[$type])
                ->values();

            // the first 12 start today, the rest start within the next 6 months
            $start = $today->copy()->addDays($i < 12 ? 0 : mt_rand(0, 180));

            if (mt_rand(1, 4) === 1) {
                $rentType = 'monthly';
                $days = null;
                $end = $start->copy()->addMonthNoOverflow();
            } else {
                $rentType = 'daily';
                $days = mt_rand(1, $start->daysInMonth - 1);
                $end = $start->copy()->addDays($days);
            }

            // find a garage that is free for these dates
            $garage = null;
            for ($try = 0; $try < 20; $try++) {
                $candidate = $garages[mt_rand(0, $garages->count() - 1)];
                $clash = false;

                foreach ($booked[$candidate->id] ?? [] as [$s, $e]) {
                    if ($s->lt($end) && $e->gt($start)) {
                        $clash = true;
                        break;
                    }
                }

                if (!$clash) {
                    $garage = $candidate;
                    break;
                }
            }

            if (!$garage) {
                continue;
            }

            $booked[$garage->id][] = [$start, $end];

            // same pricing as the app: daily x days, or 25 x daily, plus charging for electric cars
            $total = $rentType === 'daily'
                ? $garage->rentPrice * $days
                : $garage->rentPrice * 25;

            if ($vehicle->type === 'electric car') {
                $total += ($vehicle->battMaximum - $vehicle->battCapacity) * $garage->electricPrice;
            }

            $rows[] = [
                'user_id' => $vehicle->user_id,
                'vehicle_id' => $vehicle->id,
                'garage_id' => $garage->id,
                'rentType' => $rentType,
                'rentStatus' => 'active',
                'dayAmount' => $days,
                'startDate' => $start->toDateString(),
                'endDate' => $end->toDateString(),
                'paymentProof' => 'payments/decoy.jpg',
                'totalCost' => $total,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        Rent::insert($rows);
    }
}