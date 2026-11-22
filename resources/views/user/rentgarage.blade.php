<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Garage Rent</title>
    <style>
        body { margin: 0; font-family: sans-serif; }
        .topbar { background: #222; color: #fff; padding: 15px 20px; font-size: 20px; }
        .list { max-width: 900px; margin: 40px auto; }
        .columns { display: flex; gap: 20px; justify-content: center; align-items: flex-start; }
        .column { flex: 1; max-width: 320px; }
        .column h3 { margin: 0 0 10px; text-align: center; }
        .box { padding: 10px 15px; margin-bottom: 10px; border: 1px solid #333; line-height: 1.6; }
        .box a { display: block; margin-top: 8px; padding: 8px; border: 1px solid #333; text-align: center; color: #222; text-decoration: none; }
        .back { display: block; text-align: center; color: #222; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="topbar">Garage Rent</div>

    <div class="list">
        @if (session('success')) <p style="color:green; text-align:center">{{ session('success') }}</p> @endif
        @foreach ($errors->all() as $error)
            <p style="color:red; text-align:center">{{ $error }}</p>
        @endforeach

        <p style="text-align:center">Available garages for your {{ $vehicle->brand }} ({{ $vehicle->type }})</p>

        <div class="columns">
            @foreach ($types as $type)
                <div class="column">
                    <h3>{{ $type }}</h3>

                    @forelse ($garages->where('garageType', $type) as $garage)
                        <div class="box">
                            <b>{{ $garage->id }}</b><br>
                            Rent price: {{ $garage->rentPrice }} / day
                            @if ($type === 'electric car' || $type === 'car')
                                <br>Electric price: {{ $garage->electricPrice }} / kWh
                            @endif
                            <a href="{{ url('/rent/' . $vehicle->id . '/' . $garage->id) }}">Rent</a>
                        </div>
                    @empty
                        <p style="text-align:center">No garages available.</p>
                    @endforelse
                </div>
            @endforeach
        </div>

        <a class="back" href="{{ url('/rent') }}">Back</a>
    </div>
</body>
</html>