<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GarageController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\RentController;
use App\Http\Controllers\VehicleController;

Route::redirect('/', '/login');

Route::view('/login', 'account.login')->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

Route::view('/register', 'account.register')->name('register')->middleware('guest');
Route::post('/register', [AuthController::class, 'register']);

# =================== ADMIN THINGS ==================
Route::middleware(['auth', 'admin'])->group(function () {
    Route::view('/admin/garages/create', 'admin.creategarage');
    Route::get('/admin/garages', [GarageController::class, 'showAllGarages']);
    Route::post('/admin/garages', [GarageController::class, 'addGarage']);

    Route::get('/admin/users', [UserController::class, 'reviewUsers']);
    Route::post('/admin/users/{user}/accept', [UserController::class, 'approve']);
    Route::post('/admin/users/{user}/decline', [UserController::class, 'decline']);

    Route::get('/admin/rents', [RentController::class, 'viewRents']);
    Route::get('/admin/rents/{rent}/extend', [RentController::class, 'extendForm']);
    Route::post('/admin/rents/{rent}/extend', [RentController::class, 'extendRent']);

    Route::get('/admin/report', [RentController::class, 'report']);
});

# =================== USER THINGS ==================
Route::view('/vehicles/create', 'user.addvehicle')->middleware('auth');
Route::get('/vehicles', [VehicleController::class, 'viewVehicles'])->middleware('auth');
Route::post('/vehicles', [VehicleController::class, 'addVehicle'])->middleware('auth');

Route::get('/rent', [VehicleController::class, 'chooseVehicle'])->middleware('auth');
Route::get('/rent/{vehicle}', [RentController::class, 'availableGarages'])->middleware('auth');
Route::get('/rent/{vehicle}/{garage}', [RentController::class, 'rentForm'])->middleware('auth');
Route::post('/rent/{vehicle}/{garage}', [RentController::class, 'addRent'])->middleware('auth');

Route::get('/rents', [RentController::class, 'viewUserRents'])->middleware('auth');

# =================== GENERAL ==================
Route::view('/home', 'home')->middleware('auth');