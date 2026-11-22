<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Garage Rent</title>
    <style>
        body { margin: 0; font-family: sans-serif; }
        .topbar { background: #222; color: #fff; padding: 15px 20px; font-size: 20px; }
        .list { width: 320px; margin: 40px auto; }
        .box { padding: 15px; margin-bottom: 10px; border: 1px solid #333; line-height: 1.6; }
        .buttons { display: flex; gap: 10px; margin-top: 10px; }
        .buttons form { flex: 1; }
        button { width: 100%; padding: 8px; }
        a { display: block; text-align: center; color: #222; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="topbar">Garage Rent</div>

    <div class="list">
        @forelse ($users as $user)
            <div class="box">
                Name: {{ $user->fullName }}<br>
                NIK: {{ $user->nik }}

                <div class="buttons">
                    <form method="POST" action="{{ url('/admin/users/' . $user->id . '/accept') }}">
                        @csrf
                        <button type="submit">Accept</button>
                    </form>
                    <form method="POST" action="{{ url('/admin/users/' . $user->id . '/decline') }}">
                        @csrf
                        <button type="submit">Decline</button>
                    </form>
                </div>
            </div>
        @empty
            <p style="text-align:center">No users to review.</p>
        @endforelse

        <a href="{{ url('/home') }}">Back</a>
    </div>
</body>
</html>