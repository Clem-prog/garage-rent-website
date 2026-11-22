<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Garage Rent</title>
    <style>
        body { margin: 0; font-family: sans-serif; }
        .topbar { background: #222; color: #fff; padding: 15px 20px; font-size: 20px; }
        .box { width: 280px; margin: 60px auto; padding: 25px; border: 1px solid #333; }
        input, button { display: block; width: 100%; margin-bottom: 10px; padding: 8px; box-sizing: border-box; }
        a { display: block; text-align: center; color: #222; }
    </style>
</head>
<body>
    <div class="topbar">Garage Rent</div>

    <form class="box" method="POST" action="{{ url('/admin/rents/' . $rent->id . '/extend') }}" enctype="multipart/form-data">
        @csrf
        @foreach ($errors->all() as $error)
            <p style="color:red">{{ $error }}</p>
        @endforeach

        <p>
            Renter: {{ $rent->user->fullName }}<br>
            Garage: <b>{{ $rent->garage_id }}</b><br>
            Current end date: {{ $rent->endDate->toDateString() }}<br>
            Paid so far: {{ number_format($rent->totalCost) }}
        </p>

        <input type="number" id="days" name="days" placeholder="Extra days" min="1" value="{{ old('days') }}" required>
        <input type="text" id="newEnd" value="New end date" disabled>
        <p id="extra"></p>
        <input type="file" name="paymentProof" accept="image/*" required>
        <button type="submit">Extend</button>
        <a href="{{ url('/admin/rents') }}">Back</a>
    </form>

    <script>
        const daysInput = document.getElementById('days');
        const newEnd = document.getElementById('newEnd');
        const extra = document.getElementById('extra');

        const price = {{ $rent->garage->rentPrice }};
        const paid = {{ (float) $rent->totalCost }};
        const end = new Date('{{ $rent->endDate->toDateString() }}T00:00:00');

        function update() {
            const days = parseInt(daysInput.value);

            if (!(days >= 1)) {
                newEnd.value = 'New end date';
                extra.textContent = '';
                return;
            }

            // whole 30-day blocks are priced monthly (25 x daily), the rest daily
            const months = Math.floor(days / 30);
            const rest = days % 30;
            const cost = (months * 25 + rest) * price;

            const date = new Date(end);
            date.setDate(date.getDate() + days);

            newEnd.value = 'New end date: ' + date.toLocaleDateString('en-CA');
            extra.textContent = 'Extra cost: ' + cost.toLocaleString() + ' (' + months + ' monthly + ' + rest + ' daily). New total: ' + (paid + cost).toLocaleString();
        }

        daysInput.addEventListener('input', update);
        update();
    </script>
</body>
</html>