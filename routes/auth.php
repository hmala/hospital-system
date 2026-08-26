<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Authentication Routes
Auth::routes();

Route::get('logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout']);

