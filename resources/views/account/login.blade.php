<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Garage Rent</title>
    <style>
        body { margin: 0; font-family: sans-serif; }
        .topbar { background: #222; color: #fff; padding: 15px 20px; font-size: 20px; }
        .box { width: 280px; margin: 80px auto; padding: 25px; border: 1px solid #333; }
        input, button { display: block; width: 100%; margin-bottom: 10px; padding: 8px; box-sizing: border-box; }
        a { display: block; text-align: center; color: #222; }
    </style>
</head>
<body>
    <div class="topbar">Garage Rent</div>

    <form class="box" method="POST" action="{{ route('login') }}">
        @csrf 
        @if (session('success')) <p style="color:green">{{ session('success') }}</p> @endif
        @error('email') <p style="color:red">{{ $message }}</p> @enderror
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Sign in</button>
        <a href="{{ route('register') }}">Register</a>
    </form>
</body>
</html>