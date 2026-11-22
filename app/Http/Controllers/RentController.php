<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Garage;
use App\Models\Vehicle;
use App\Models\Rent;
use Illuminate\Support\Carbon;

class RentController extends Controller
{
# ========================================================== USER ===========================================================
    private array $garageTypes = [
        'bicycle' => ['bicycle'],
        'motorcycle' => ['bicycle'],
        'car' => ['car'],
        'electric car' => ['car', 'electric car'],
    ];
 
    // find garages that fit the vehicle and are free today
    public function availableGarages(Request $request, Vehicle $vehicle)
    {
        abort_unless($vehicle->user_id == $request->user()->id, 403);
 
        $types = $this->garageTypes[$vehicle->type];
 
        $rented = Rent::whereDate('startDate', '<=', today())
            ->whereDate('endDate', '>', today())
            ->pluck('garage_id');
 
        $garages = Garage::whereIn('garageType', $types)
            ->whereNotIn('id', $rented)
            ->orderBy('rentPrice')
            ->orderBy('id')
            ->get();
 
        return view('user.rentgarage', [
            'vehicle' => $vehicle,
            'types' => $types,
            'garages' => $garages,
        ]);
    }

    public function viewUserRents(Request $request)
    {
        $rents = Rent::with(['user', 'vehicle'])
            ->where('user_id', $request->user()->id)
            ->whereDate('endDate', '>', today())
            ->orderBy('startDate')
            ->get();

        return view('user.viewrent', ['rents' => $rents]);
    }

    public function rentForm(Request $request, Vehicle $vehicle, Garage $garage)
    {
        $this->checkAccess($request, $vehicle, $garage);
 
        if ($this->isRented($garage)) {
            return redirect('/rent/' . $vehicle->id)
                ->withErrors(['garage' => 'That garage is already in use.']);
        }
 
        return view('user.rentform', [
            'vehicle' => $vehicle,
            'garage' => $garage,
            'charge' => $this->chargeCost($vehicle, $garage),
            'today' => today()->toDateString(),
        ]);
    }

    public function addRent(Request $request, Vehicle $vehicle, Garage $garage)
    {
        $this->checkAccess($request, $vehicle, $garage);

        // the start date can be today or later, and decides the max number of days
        $startDate = $request->validate([
            'startDate' => 'required|date|after_or_equal:today',
        ])['startDate'];

        $start = Carbon::parse($startDate)->startOfDay();
        $maxDays = $start->daysInMonth - 1;

        $data = $request->validate([
            'rentType' => 'required|in:daily,monthly',
            'dayAmount' => 'nullable|integer|min:1|max:' . $maxDays . '|required_if:rentType,daily',
            'paymentProof' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        if ($data['rentType'] === 'daily') {
            $days = (int) $data['dayAmount'];
            $end = $start->copy()->addDays($days);
        } else {
            $days = null;
            $end = $start->copy()->addMonthNoOverflow();
        }

        // reject if the garage is already booked for any of these dates
        if ($this->isBooked($garage, $start, $end)) {
            return back()
                ->withInput()
                ->withErrors(['startDate' => 'That garage is already booked for those dates.']);
        }

        // saves the image in storage/app/public/payments, returns its path
        $path = $request->file('paymentProof')->store('payments', 'public');
        $total = $this->totalCost($vehicle, $garage, $data['rentType'], $days);

        Rent::create([
            'user_id' => $request->user()->id,
            'vehicle_id' => $vehicle->id,
            'garage_id' => $garage->id,
            'rentType' => $data['rentType'],
            'rentStatus' => 'active',
            'dayAmount' => $days,
            'startDate' => $start,
            'endDate' => $end,
            'paymentProof' => $path,
            'totalCost' => $total,
        ]);

        return redirect('/rent/' . $vehicle->id)
            ->with('success', 'Garage ' . $garage->id . ' rented.');
    }

    // check if vehicle is user's and garage is compatible with vehicle type
    private function checkAccess(Request $request, Vehicle $vehicle, Garage $garage): void
    {
        abort_unless($vehicle->user_id == $request->user()->id, 403);
        abort_unless(in_array($garage->garageType, $this->garageTypes[$vehicle->type]), 403);
    }

    private function isRented(Garage $garage): bool
    {
        return Rent::where('garage_id', $garage->id)
            ->whereDate('startDate', '<=', today())
            ->whereDate('endDate', '>', today())
            ->exists();
    }

    // does any existing rent overlap the chosen dates?
    private function isBooked(Garage $garage, Carbon $start, Carbon $end): bool
    {
        return Rent::where('garage_id', $garage->id)
            ->whereDate('startDate', '<', $end)
            ->whereDate('endDate', '>', $start)
            ->exists();
    }

    private function chargeCost(Vehicle $vehicle, Garage $garage): float
    {
        if ($vehicle->type !== 'electric car') {
            return 0;
        }
 
        return ($vehicle->battMaximum - $vehicle->battCapacity) * $garage->electricPrice;
    }

    private function totalCost(Vehicle $vehicle, Garage $garage, string $rentType, ?int $days): float
    {
        $rent = $rentType === 'daily'
            ? $garage->rentPrice * $days
            : $garage->rentPrice * 25;

        return $rent + $this->chargeCost($vehicle, $garage);
    }

# ================================================== ADMIN ===================================================
    public function viewRents()
    {
        $rents = Rent::with(['user', 'vehicle'])
            ->whereDate('endDate', '>', today())
            ->orderBy('startDate')
            ->get();
 
        return view('admin.viewrent', ['rents' => $rents]);
    }
 
    public function extendForm(Rent $rent)
    {
        return view('admin.extendrent', ['rent' => $rent]);
    }
 
    public function extendRent(Request $request, Rent $rent)
    {
        $data = $request->validate([
            'days' => 'required|integer|min:1',
            'paymentProof' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $days = (int) $data['days'];
        $oldEnd = $rent->endDate;
        $newEnd = $oldEnd->copy()->addDays($days);

        // the garage must be free for the extra days
        $clash = Rent::where('garage_id', $rent->garage_id)
            ->where('id', '!=', $rent->id)
            ->whereDate('startDate', '<', $newEnd)
            ->whereDate('endDate', '>', $oldEnd)
            ->exists();

        if ($clash) {
            return back()
                ->withInput()
                ->withErrors(['days' => 'The garage is booked by someone else during that period.']);
        }

        // saves the new proof in storage/app/public/payments, returns its path
        $path = $request->file('paymentProof')->store('payments', 'public');

        $rent->endDate = $newEnd;
        $rent->totalCost += $this->extensionCost($rent->garage, $days);
        $rent->paymentProof = $path;
        $rent->save();

        return redirect('/admin/rents')
            ->with('success', 'Rent of garage ' . $rent->garage_id . ' extended to ' . $newEnd->toDateString() . '.');
    }
 
    // whole 30-day blocks are priced monthly (25 x daily), the rest daily
    private function extensionCost(Garage $garage, int $days): float
    {
        $months = intdiv($days, 30);
        $rest = $days % 30;
 
        return ($months * 25 + $rest) * $garage->rentPrice;
    }

# ================================================== Report Feature ===================================================
    public function report(Request $request)
    {
        $data = $request->validate([
            'type' => 'nullable|in:month,day,garage,renter',
            'year' => 'nullable|integer|min:2000|max:2100',
            'month' => 'nullable|integer|min:1|max:12',
        ]);
 
        $type = $data['type'] ?? 'month';
        $year = (int) ($data['year'] ?? now()->year);
        $month = (int) ($data['month'] ?? now()->month);
 
        if ($type === 'month') {
            $title = 'Rents per month in ' . $year;
            $header = 'Month';
            $rows = $this->byMonth($year);
        } elseif ($type === 'day') {
            $title = 'Rents per day in ' . date('F', mktime(0, 0, 0, $month, 1)) . ' ' . $year;
            $header = 'Day';
            $rows = $this->byDay($year, $month);
        } elseif ($type === 'garage') {
            $title = 'Rents per garage type';
            $header = 'Garage type';
            $rows = $this->byGarageType();
        } else {
            $title = 'Rents per renter';
            $header = 'Renter';
            $rows = $this->byRenter();
        }
 
        return view('admin.reportrent', [
            'type' => $type,
            'year' => $year,
            'month' => $month,
            'title' => $title,
            'header' => $header,
            'rows' => $rows,
        ]);
    }
 
    // rents are counted in the month and day their startDate falls in
    private function byMonth(int $year): array
    {
        $found = Rent::whereYear('startDate', $year)
            ->selectRaw('MONTH(startDate) as part, COUNT(*) as rent_count, SUM(totalCost) as total')
            ->groupBy('part')
            ->get()
            ->keyBy('part');
 
        $rows = [];
        for ($m = 1; $m <= 12; $m++) {
            $rows[] = $this->row(date('F', mktime(0, 0, 0, $m, 1)), $found->get($m));
        }
 
        return $rows;
    }
 
    private function byDay(int $year, int $month): array
    {
        $found = Rent::whereYear('startDate', $year)
            ->whereMonth('startDate', $month)
            ->selectRaw('DAY(startDate) as part, COUNT(*) as rent_count, SUM(totalCost) as total')
            ->groupBy('part')
            ->get()
            ->keyBy('part');
 
        $rows = [];
        $daysInMonth = Carbon::create($year, $month, 1)->daysInMonth;
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $rows[] = $this->row((string) $d, $found->get($d));
        }
 
        return $rows;
    }
 
    private function byGarageType(): array
    {
        $found = Rent::join('garages', 'garages.id', '=', 'rents.garage_id')
            ->selectRaw('garages.garageType as part, COUNT(*) as rent_count, SUM(rents.totalCost) as total')
            ->groupBy('garages.garageType')
            ->get()
            ->keyBy('part');
 
        $rows = [];
        foreach (['bicycle', 'car', 'electric car'] as $type) {
            $rows[] = $this->row($type, $found->get($type));
        }
 
        return $rows;
    }
 
    private function byRenter(): array
    {
        return Rent::join('users', 'users.id', '=', 'rents.user_id')
            ->selectRaw('users.fullName as label, COUNT(*) as rent_count, SUM(rents.totalCost) as total')
            ->groupBy('users.id', 'users.fullName')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => $this->row($r->label, $r))
            ->all();
    }
 
    // one table row, zeros when nothing was found
    private function row(string $label, ?Rent $found): array
    {
        return [
            'label' => $label,
            'count' => $found?->rent_count ?? 0,
            'total' => $found?->total ?? 0,
        ];
    }
}