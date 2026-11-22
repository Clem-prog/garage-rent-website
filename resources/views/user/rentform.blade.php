<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Garage Rent</title>
    <style>
        body { margin: 0; font-family: sans-serif; }
        .topbar { background: #222; color: #fff; padding: 15px 20px; font-size: 20px; }
        .box { width: 280px; margin: 60px auto; padding: 25px; border: 1px solid #333; }
        input, select, button { display: block; width: 100%; margin-bottom: 10px; padding: 8px; box-sizing: border-box; }
        label { display: block; margin-bottom: 4px; }
        a { display: block; text-align: center; color: #222; }
    </style>
</head>
<body>
    <div class="topbar">Garage Rent</div>

    <form class="box" method="POST" action="{{ url('/rent/' . $vehicle->id . '/' . $garage->id) }}" enctype="multipart/form-data">
        @csrf
        @foreach ($errors->all() as $error)
            <p style="color:red">{{ $error }}</p>
        @endforeach

        <p>
            Garage: <b>{{ $garage->id }}</b><br>
            Vehicle: {{ $vehicle->brand }} ({{ $vehicle->type }})
        </p>

        <select name="rentType" id="rentType" required>
            <option value="" disabled selected>Rent type</option>
            <option value="daily" @selected(old('rentType') == 'daily')>daily</option>
            <option value="monthly" @selected(old('rentType') == 'monthly')>monthly</option>
        </select>

        <label for="dayAmount" id="dayLabel" style="display:none">Number of days</label>
        <input type="number" id="dayAmount" name="dayAmount" min="1" value="{{ old('dayAmount') }}" style="display:none">

        <label for="startDate">Start date</label>
        <input type="date" id="startDate" name="startDate" min="{{ $today }}" value="{{ old('startDate', $today) }}" required>

        <input type="text" id="endDate" value="End date" disabled>
        <p id="total"></p>
        <input type="file" name="paymentProof" accept="image/*" required>
        <button type="submit">Rent</button>
        <a href="{{ url('/rent/' . $vehicle->id) }}">Back</a>
    </form>

    <script>
        const rentType = document.getElementById('rentType');
        const dayAmount = document.getElementById('dayAmount');
        const dayLabel = document.getElementById('dayLabel');
        const startDate = document.getElementById('startDate');
        const endDate = document.getElementById('endDate');
        const total = document.getElementById('total');

        const price = {{ $garage->rentPrice }};
        const charge = {{ $charge }};

        function daysInMonth(d) {
            return new Date(d.getFullYear(), d.getMonth() + 1, 0).getDate();
        }

        function addMonth(d) {
            const r = new Date(d.getFullYear(), d.getMonth() + 1, d.getDate());
            if (r.getDate() !== d.getDate()) r.setDate(0); // overflowed, use the last day of the month
            return r;
        }

        function update() {
            const isDaily = rentType.value === 'daily';
            const isMonthly = rentType.value === 'monthly';
            const start = startDate.value ? new Date(startDate.value + 'T00:00:00') : null;

            dayAmount.style.display = isDaily ? 'block' : 'none';
            dayLabel.style.display = isDaily ? 'block' : 'none';
            dayAmount.required = isDaily;
            if (!isDaily) dayAmount.value = '';

            // daily rents must be shorter than the month of the start date
            const maxDays = start ? daysInMonth(start) - 1 : 0;
            dayAmount.max = maxDays;
            dayLabel.textContent = start
                ? 'Number of days (max ' + maxDays + ' in ' + start.toLocaleString('en-US', { month: 'long' }) + ')'
                : 'Number of days';

            const days = parseInt(dayAmount.value);
            let end = null;
            let rent = 0;

            if (start && isDaily && days >= 1 && days <= maxDays) {
                end = new Date(start);
                end.setDate(end.getDate() + days);
                rent = price * days;
            } else if (start && isMonthly) {
                end = addMonth(start);
                rent = price * 25;
            }

            if (end) {
                endDate.value = 'End date: ' + end.toLocaleDateString('en-CA');
            } else if (isDaily && days > maxDays) {
                endDate.value = 'Too long, max ' + maxDays + ' days';
            } else {
                endDate.value = 'End date';
            }

            if (!end) {
                total.textContent = '';
            } else if (charge > 0) {
                total.textContent = 'Total cost: ' + (rent + charge).toLocaleString() + ' (includes charging ' + charge.toLocaleString() + ')';
            } else {
                total.textContent = 'Total cost: ' + rent.toLocaleString();
            }
        }

        rentType.addEventListener('change', update);
        dayAmount.addEventListener('input', update);
        startDate.addEventListener('input', update);
        startDate.addEventListener('change', update);
        update();
    </script>
</body>
</html>