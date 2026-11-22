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
        a { display: block; text-align: center; color: #222; }
    </style>
</head>
<body>
    <div class="topbar">Garage Rent</div>

    <form class="box" method="POST" action="{{ url('/vehicles') }}">
        @csrf
        @if (session('success')) <p style="color:green">{{ session('success') }}</p> @endif
        @foreach ($errors->all() as $error)
            <p style="color:red">{{ $error }}</p>
        @endforeach

        <select name="type" required>
            <option value="" disabled selected>Vehicle type</option>
            <option value="bicycle" @selected(old('type') == 'bicycle')>bicycle</option>
            <option value="motorcycle" @selected(old('type') == 'motorcycle')>motorcycle</option>
            <option value="car" @selected(old('type') == 'car')>car</option>
            <option value="electric car" @selected(old('type') == 'electric car')>electric car</option>
        </select>
        <input type="text" id="plateNum" name="plateNum" placeholder="Plate number" value="{{ old('plateNum') }}">
        <input type="text" name="brand" placeholder="Brand" value="{{ old('brand') }}" required>

        <div id="battery" style="display:none">
            <input type="number" name="battCapacity" placeholder="Battery capacity" min="0" step="any" value="{{ old('battCapacity') }}">
            <input type="number" name="battMaximum" placeholder="Battery maximum" min="0" step="any" value="{{ old('battMaximum') }}">
        </div>

        <button type="submit">Add</button>
        <a href="{{ url('/home') }}">Back</a>
    </form>

    <script>
        const type = document.querySelector('[name=type]');
        const plate = document.getElementById('plateNum');
        const battery = document.getElementById('battery');

        function toggle() {
            const isBike = type.value === 'bicycle';
            const isEV = type.value === 'electric car';

            plate.style.display = isBike ? 'none' : 'block';
            plate.required = !isBike;
            if (isBike) plate.value = '';

            battery.style.display = isEV ? 'block' : 'none';
            battery.querySelectorAll('input').forEach(function (input) {
                input.required = isEV;
                if (!isEV) input.value = '';
            });
        }

        type.addEventListener('change', toggle);
        toggle();
    </script>
</body>
</html>