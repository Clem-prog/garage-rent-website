<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function addVehicle(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:bicycle,motorcycle,car,electric car',
            'plateNum' => 'nullable|string|max:15|required_unless:type,bicycle',
            'brand' => 'required|string|max:255',
            'battCapacity' => 'nullable|numeric|min:0|lte:battMaximum|required_if:type,electric car',
            'battMaximum' => 'nullable|numeric|min:0|required_if:type,electric car',
        ], [
            'battCapacity.lte' => 'Battery capacity must be less than or equal to battery maximum.',
        ]);
 
        // bicycles have no plate, only electric cars have a battery
        if ($data['type'] === 'bicycle') {
            $data['plateNum'] = null;
        }
 
        if ($data['type'] !== 'electric car') {
            $data['battCapacity'] = null;
            $data['battMaximum'] = null;
        }
 
        $data['user_id'] = Auth::id();
 
        Vehicle::create($data);
 
        return back()->with('success', 'Vehicle added.');
    }

    public function chooseVehicle(Request $request)
    {
        $vehicles = Vehicle::where('user_id', $request->user()->id)->get();
 
        return view('user.choosevehicle', ['vehicles' => $vehicles]);
    }

    public function viewVehicles(Request $request)
    {
        $vehicles = Vehicle::where('user_id', $request->user()->id)->get();

        return view('user.viewvehicle', ['vehicles' => $vehicles]);
    }
}
