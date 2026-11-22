<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Garage Rent</title>
    <style>
        body { margin: 0; font-family: sans-serif; }
        .topbar { background: #222; color: #fff; padding: 15px 20px; font-size: 20px; }
        .list { width: 900px; margin: 40px auto; }
        .columns { display: flex; gap: 20px; align-items: flex-start; }
        .column { flex: 1; }
        .column h3 { margin: 0 0 10px; text-align: center; }
        .box { padding: 15px; margin-bottom: 10px; border: 1px solid #333; line-height: 1.6; }
        a { display: block; text-align: center; color: #222; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="topbar">Garage Rent</div>

    <div class="list">
        <div class="columns">
            @foreach (['bicycle', 'car', 'electric car'] as $type)
                <div class="column">
                    <h3>{{ $type }}</h3>

                    @forelse (collect($batches)->where('garageType', $type) as $batch)
                        <div class="box">
                            <b>{{ $batch['firstId'] }} - {{ $batch['lastId'] }}</b> ({{ $batch['count'] }} garages)<br>
                            Rent price: {{ $batch['rentPrice'] }}<br>
                            @if ($type === 'electric car')
                                Electric price: {{ $batch['electricPrice'] }} / kWh
                            @endif
                        </div>
                    @empty
                        <p style="text-align:center">No garages yet.</p>
                    @endforelse
                </div>
            @endforeach
        </div>

        <a href="{{ url('/home') }}">Back</a>
    </div>
</body>
</html>