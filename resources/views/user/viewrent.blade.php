<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Garage Rent</title>
    <style>
        body { margin: 0; font-family: sans-serif; }
        .topbar { background: #222; color: #fff; padding: 15px 20px; font-size: 20px; }
        .list { width: 340px; margin: 40px auto; }
        .box { padding: 15px; margin-bottom: 10px; border: 1px solid #333; line-height: 1.6; }
        .box img { display: block; width: 100%; margin-top: 10px; }
        .back { display: block; text-align: center; color: #222; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="topbar">Garage Rent</div>

    <div class="list">
        @forelse ($rents as $rent)
            <div class="box">
                Garage: <b>{{ $rent->garage_id }}</b><br>
                Vehicle: {{ $rent->vehicle->type }}<br>
                Rent type: {{ $rent->rentType }}<br>
                Start date: {{ $rent->startDate->toDateString() }}<br>
                End date: {{ $rent->endDate->toDateString() }}<br>
                Paid: {{ number_format($rent->totalCost) }}
                <img src="{{ asset('storage/' . $rent->paymentProof) }}" alt="Payment proof">
            </div>
        @empty
            <p style="text-align:center">No rents yet.</p>
        @endforelse

        <a class="back" href="{{ url('/home') }}">Back</a>
    </div>
</body>
</html>