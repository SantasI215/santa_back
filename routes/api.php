<?php

use App\Http\Controllers\BoxController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\AdminController;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
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
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
Route::middleware('auth:sanctum')->prefix('admin')->group(function() {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/orders', [AdminController::class, 'getOrders']);
    // Добавьте другие маршруты, необходимые для админки
});
Route::middleware([EnsureFrontendRequestsAreStateful::class])->get('/user', function (Request $request) {
    return $request->user();
});
Route::middleware(['auth:sanctum', 'admin'])->get('/admin-dashboard', [AdminController::class, 'dashboard']);