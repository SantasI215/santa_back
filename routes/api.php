<?php

use App\Http\Controllers\BoxController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ConfiguratorController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\ItemController;
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

// Авторизация
Route::post('/register', [RegisterController::class, 'register']);
Route::post('/login', [LoginController::class, 'login']);
// Авторизация

// Пользователь
Route::middleware(['auth:sanctum'])->get('/user', [UserController::class, 'getUser']);
Route::middleware('auth:sanctum')->get('/orders', [OrderController::class, 'getUserOrders']);
// Пользователь

// Коробки
Route::get('/new-boxes', [BoxController::class, 'newBoxes']);
Route::get('/all-boxes', [BoxController::class, 'index']);
Route::get('/boxes/{id}', [BoxController::class, 'showDetail']);
Route::middleware(['auth:sanctum', 'admin'])->post('/boxes', [BoxController::class, 'store']);
// Коробки

// Категории
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']);
});
// Категории

// Корзина
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/cart/add/{boxId}', [CartController::class, 'addToCart']);
    Route::post('/configurator/create-and-add', [ConfiguratorController::class, 'createAndAddToCart']);
    Route::get('/cart', [CartController::class, 'viewCart']);
    Route::delete('/cart/remove/{boxId}', [CartController::class, 'removeFromCart']);
});
// Корзина

// Оформление
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/checkout', [OrderController::class, 'checkout']); // Отображение корзины перед заказом
    Route::post('/place-order', [OrderController::class, 'placeOrder']); // Оформление заказа
});
// Оформление

// Админ
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function() {
    Route::get('/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/orders', [AdminController::class, 'getOrders']);
    Route::get('/users', function () {
        return response()->json(App\Models\User::all());
    });
    Route::delete('/users/{id}/delete', [AdminController::class, 'deleteUser']);
    Route::delete('/items/{id}/delete', [AdminController::class, 'deleteItem']);
});
// Админ

// Товары
Route::middleware(['auth:sanctum'])->get('/items', function () {
    return response()->json(App\Models\Item::all());
});
Route::middleware(['auth:sanctum', 'admin'])->post('/items', [ItemController::class, 'store']);
Route::middleware(['auth:sanctum', 'admin'])->delete('/items/{id}/delete', [AdminController::class, 'deleteItem']);
// Товары
