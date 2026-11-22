<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($credentials)) {
            if (!Auth::user()->isApproved) {
                Auth::logout();
                return back()->withErrors(['email' => 'Your account is not approved yet.']);
            }

            $request->session()->regenerate();
            return redirect('/home');
        }

        return back()->withErrors(['email' => 'Wrong email or password.']);
    }

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'nik' => 'required|string|size:16|unique:users',
            'fullName' => 'required|string|max:255',
            'phone' => 'required|string|max:16|min:10|unique:users',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create($validatedData);

        return redirect('/login')->with('success', 'Registration successful. Please wait for admin approval.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}
