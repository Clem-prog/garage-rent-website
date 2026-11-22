<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function reviewUsers()
    {
        $users = User::where('isApproved', false)
            ->where('isAdmin', false)
            ->get();
 
        return view('admin.reviewuser', ['users' => $users]);
    }
 
    public function approve(User $user)
    {
        $user->isApproved = true;
        $user->save();
 
        return back();
    }
 
    public function decline(User $user)
    {
        // only pending, non-admin accounts can be declined
        if ($user->isApproved || $user->isAdmin) {
            abort(403);
        }
 
        $user->delete();
 
        return back();
    }
}
