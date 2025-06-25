<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;

Route::get('/api/auth/init', [AuthController::class, 'initializeAuth']);

// Route::get('/', function () {
//     return view('welcome');
// });
