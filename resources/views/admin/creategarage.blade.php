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

    <form class="box" method="POST" action="{{ url('/admin/garages') }}">
        @csrf
        @if (session('success')) <p style="color:green">{{ session('success') }}</p> @endif
        @foreach ($errors->all() as $error)
            <p style="color:red">{{ $error }}</p>
        @endforeach

        <select name="garageType" required>
            <option value="" disabled selected>Garage type</option>
            <option value="bicycle" @selected(old('garageType') == 'bicycle')>bicycle</option>
            <option value="car" @selected(old('garageType') == 'car')>car</option>
            <option value="electric car" @selected(old('garageType') == 'electric car')>electric car</option>
        </select>
        <input type="number" name="rentPrice" placeholder="Rent price (daily)" min="0" value="{{ old('rentPrice') }}" required>
        <input type="number" name="amount" placeholder="Amount (max 999)" min="1" max="999" value="{{ old('amount') }}" required>
        <input type="number" name="electricPrice" id="electricPrice" placeholder="Electric price per kWh" min="0" value="{{ old('electricPrice') }}" style="display:none">
        <button type="submit">Create</button>
        <a href="{{ url('/home') }}">Back</a>
    </form>
</body>
</html>
<script>
    const type = document.querySelector('[name=garageType]');
    const price = document.getElementById('electricPrice')

    function toggle() {
        const show = type.value === 'electric car' || type.value === 'car';
        price.style.display = show ? 'block' : 'none';
        price.required = show;
        if (!show) price.value = '';
    }

    type.addEventListener('change', toggle);
    toggle();
</script>