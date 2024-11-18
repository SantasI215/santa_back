<?php

use App\Http\Controllers\BoxController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::get('/boxes-new', [BoxController::class, 'indexNew']);
Route::get('/boxes', [BoxController::class, 'index']);
Route::get('/boxes/{id}', [BoxController::class, 'show']);
