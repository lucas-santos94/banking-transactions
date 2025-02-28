<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountController;

Route::post('/account/transaction', [AccountController::class, 'transaction']);
