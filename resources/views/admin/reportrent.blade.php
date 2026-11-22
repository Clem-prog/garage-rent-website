<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Garage Rent</title>
    <style>
        body { margin: 0; font-family: sans-serif; }
        .topbar { background: #222; color: #fff; padding: 15px 20px; font-size: 20px; }
        .page { width: 500px; margin: 40px auto; }
        .filters { display: flex; gap: 10px; margin-bottom: 20px; }
        .filters select, .filters input, .filters button { padding: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #333; padding: 8px; text-align: left; }
        td.num, th.num { text-align: right; }
        .back { display: block; text-align: center; color: #222; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="topbar">Garage Rent</div>

    <div class="page">
        @foreach ($errors->all() as $error)
            <p style="color:red">{{ $error }}</p>
        @endforeach

        <form class="filters" method="GET" action="{{ url('/admin/report') }}">
            <select name="type" id="type">
                <option value="month" @selected($type == 'month')>Per month</option>
                <option value="day" @selected($type == 'day')>Per day</option>
                <option value="garage" @selected($type == 'garage')>Per garage type</option>
                <option value="renter" @selected($type == 'renter')>Per renter</option>
            </select>
            <input type="number" id="year" name="year" value="{{ $year }}" placeholder="Year" min="2000" max="2100" style="width:80px">
            <select name="month" id="month">
                @for ($m = 1; $m <= 12; $m++)
                    <option value="{{ $m }}" @selected($month == $m)>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                @endfor
            </select>
            <button type="submit">Show</button>
        </form>

        <h3>{{ $title }}</h3>

        <table>
            <tr>
                <th>{{ $header }}</th>
                <th class="num">Rents</th>
                <th class="num">Income</th>
            </tr>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td class="num">{{ $row['count'] }}</td>
                    <td class="num">{{ number_format($row['total']) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" style="text-align:center">No rents yet.</td></tr>
            @endforelse
            <tr>
                <th>Total</th>
                <th class="num">{{ collect($rows)->sum('count') }}</th>
                <th class="num">{{ number_format(collect($rows)->sum('total')) }}</th>
            </tr>
        </table>

        <a class="back" href="{{ url('/home') }}">Back</a>
    </div>

    <script>
        const type = document.getElementById('type');
        const year = document.getElementById('year');
        const month = document.getElementById('month');

        function toggle() {
            year.style.display = (type.value === 'month' || type.value === 'day') ? 'inline-block' : 'none';
            month.style.display = type.value === 'day' ? 'inline-block' : 'none';
        }

        type.addEventListener('change', toggle);
        toggle();
    </script>
</body>
</html>