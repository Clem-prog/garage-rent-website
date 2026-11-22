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

    <form class="box" method="POST" action="{{ url('/register') }}">
        @csrf
        @foreach ($errors->all() as $error)
            <p style="color:red">{{ $error }}</p>
        @endforeach
        <input type="text" name="nik" placeholder="NIK" inputmode="numeric" required>
        <input type="text" name="fullName" placeholder="Full name" required>
        <input type="tel" name="phone" placeholder="Phone" inputmode="numeric" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <input type="password" name="password_confirmation" placeholder="Confirm password" required>
        <button type="submit">Register</button>
        <a href="{{ route('login') }}">Sign in</a>
    </form>
</body>
</html>