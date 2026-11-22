<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Garage Rent</title>
    <style>
        body { margin: 0; font-family: sans-serif; }
        .topbar { background: #222; color: #fff; padding: 15px 20px; font-size: 20px; }
        .list { width: 320px; margin: 40px auto; }
        .box { padding: 15px; margin-bottom: 10px; border: 1px solid #333; line-height: 1.6; }
        .box a { display: block; margin-top: 10px; padding: 8px; border: 1px solid #333; text-align: center; color: #222; text-decoration: none; }
        .back { display: block; text-align: center; color: #222; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="topbar">Garage Rent</div>

    <div class="list">
        <p style="text-align:center">Choose a vehicle</p>

        @forelse ($vehicles as $vehicle)
            <div class="box">
                Type: {{ $vehicle->type }}<br>
                Brand: {{ $vehicle->brand }}
                @if ($vehicle->plateNum)
                    <br>Plate number: {{ $vehicle->plateNum }}
                @endif
                <a href="{{ url('/rent/' . $vehicle->id) }}">Select</a>
            </div>
        @empty
            <p style="text-align:center">No vehicles yet.</p>
            <a class="back" href="{{ url('/vehicles/create') }}">Add a vehicle</a>
        @endforelse

        <a class="back" href="{{ url('/home') }}">Back</a>
    </div>
</body>
</html>