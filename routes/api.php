<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AllblogpostController;

Route::get('/allblogposts', [AllblogpostController::class, 'index']);
