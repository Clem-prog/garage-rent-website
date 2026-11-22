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
        a { display: block; text-align: center; color: #222; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="topbar">Garage Rent</div>

    <div class="list">
        @forelse ($vehicles as $vehicle)
            <div class="box">
                Type: {{ $vehicle->type }}<br>
                Brand: {{ $vehicle->brand }}<br>
                @if ($vehicle->plateNum)
                    Plate number: {{ $vehicle->plateNum }}<br>
                @endif
                @if ($vehicle->type === 'electric car')
                    Battery capacity: {{ (float) $vehicle->battCapacity }}<br>
                    Battery maximum: {{ (float) $vehicle->battMaximum }}
                @endif
            </div>
        @empty
            <p style="text-align:center">No vehicles yet.</p>
        @endforelse

        <a href="{{ url('/home') }}">Back</a>
    </div>
</body>
</html>