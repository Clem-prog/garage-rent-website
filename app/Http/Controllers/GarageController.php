<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Garage;
use Illuminate\Validation\Rule;
use Illuminate\Http\Request;

class GarageController extends Controller
{
    private array $prefixes = [
        'bicycle' => 'S',
        'car' => 'C',
        'electric car' => 'E',
    ];

    public function addGarage(Request $request)
    {
        $data = $request->validate([
            'garageType' => ['required', Rule::in(array_keys($this->prefixes))],
            'rentPrice' => 'required|integer|min:0',
            'amount' => 'required|integer|min:1|max:999',
            'electricPrice' => 'nullable|integer|min:0|required_if:garageType,car,electric car',
        ]);
 
        $type = $data['garageType'];
        $amount = (int) $data['amount'];
        $prefix = $this->prefixes[$type];
        $electricPrice = $type === 'bicycle' ? 0 : (int) $data['electricPrice'];
 
        // Continue numbering from the last garage of the same type
        $last = Garage::where('garageType', $type)->max('id');
        $next = $last ? (int) substr($last, 1) + 1 : 1;
 
        if ($next + $amount - 1 > 999) {
            return back()
                ->withErrors(['amount' => 'Max 999 garages per type. Only ' . (1000 - $next) . ' left for this type.'])
                ->withInput();
        }
 
        $rows = [];
        for ($i = 0; $i < $amount; $i++) {
            $rows[] = [
                'id' => $prefix . str_pad($next + $i, 3, '0', STR_PAD_LEFT),
                'garageType' => $type,
                'rentPrice' => $data['rentPrice'],
                'electricPrice' => $electricPrice,
                'amount' => $data['amount'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
 
        Garage::insert($rows);
 
        return back()->with('success', $amount . ' garage(s) created.');
    }

    public function showAllGarages()
    {
        $batches = [];
        $garages = Garage::orderBy('garageType')->orderBy('id')->get();

        foreach ($garages as $garage) {
            $last = count($batches) - 1;

            if ($last >= 0
                && $batches[$last]['garageType'] == $garage->garageType
                && $batches[$last]['rentPrice'] == $garage->rentPrice
                && $batches[$last]['electricPrice'] == $garage->electricPrice) {
                // same batch, extend it
                $batches[$last]['lastId'] = $garage->id;
                $batches[$last]['count']++;
            } else {
                // something changed, start a new box
                $batches[] = [
                    'garageType' => $garage->garageType,
                    'rentPrice' => $garage->rentPrice,
                    'electricPrice' => $garage->electricPrice,
                    'firstId' => $garage->id,
                    'lastId' => $garage->id,
                    'count' => 1,
                ];
            }
        }

        return view('admin.viewgarage', ['batches' => $batches]);
    }
}
