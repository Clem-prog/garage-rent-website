<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Garage Rent</title>
    <style>
        body { margin: 0; font-family: sans-serif; }
        .topbar { background: #222; color: #fff; padding: 15px 20px; font-size: 20px; }
        .menu { width: 280px; margin: 80px auto 20px; padding: 25px; border: 1px solid #333; }
        .menu a { display: block; margin-bottom: 10px; padding: 10px; border: 1px solid #333; text-align: center; color: #222; text-decoration: none; }
        .menu a:last-child { margin-bottom: 0; }
        .logout { text-align: center; }
    </style>
</head>
<body>
    <div class="topbar">Garage Rent</div>

    <div class="menu">
        @if (auth()->user()->isAdmin)
            <a href="{{ url('/admin/users') }}">Review Users</a>
            <a href="{{ url('/admin/garages/create') }}">Make Garage</a>
            <a href="{{ url('/admin/garages') }}">View Garages</a>
            <a href="{{ url('/admin/rents') }}">Extend Rent</a>
            <a href="{{ url('/admin/report') }}">Check Rent Report</a>
        @else
            <a href="{{ url('/vehicles/create') }}">Add Vehicle</a>
            <a href="{{ url('/vehicles') }}">View Vehicle</a>
            <a href="{{ url('/rent') }}">Rent Garage(s)</a>
            <a href="{{ url('/rents') }}">View Rent(s)</a>
        @endif
    </div>

    <form class="logout" method="POST" action="{{ url('/logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>
</body>
</html>