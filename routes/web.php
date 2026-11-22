<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GarageController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;

Route::redirect('/', '/login');

Route::view('/login', 'account.login')->name('login')->middleware('guest');;
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

Route::view('/register', 'account.register')->name('register')->middleware('guest');
Route::post('/register', [AuthController::class, 'register']);

Route::view('/admin/garages/create', 'admin.creategarage')->middleware('auth');
Route::view('/admin/garages', 'admin.viewgarage')->name('viewgarage')->middleware('auth');
Route::get('/admin/garages', [GarageController::class, 'viewGarages'])->middleware('auth');
Route::post('/admin/garages', [GarageController::class, 'addGarage'])->middleware('auth');

route::view('/admin/users', 'admin.reviewusers')->middleware('auth');
Route::get('/admin/users', [UserController::class, 'reviewUsers'])->middleware('auth');
Route::post('/admin/users/{user}/accept', [UserController::class, 'accept'])->middleware('auth');
Route::post('/admin/users/{user}/decline', [UserController::class, 'decline'])->middleware('auth');

Route::view('/home', 'home')->middleware('auth');