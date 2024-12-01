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
use App\Http\Controllers\OrderItemController;
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
    Route::get('/checkout', [OrderController::class, 'checkout']);
    Route::post('/place-order', [OrderController::class, 'placeOrder']);
});
// Оформление

// Админ
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function() {
    // Пользователи
    Route::get('/users', [AdminController::class, 'getAllUsers']);
    // Пользователи
    // Товары
    Route::get('/items', [ItemController::class, 'getAllItems']);
    Route::post('/items', [ItemController::class, 'store']);
    Route::delete('/items/{id}', [ItemController::class, 'destroy']);
    // Товары
    // Категории
    Route::post('/categories', [CategoryController::class, 'store']);
    // Категории
    // Боксы
    Route::delete('/boxes/{id}', [BoxController::class, 'destroy']);
    // Боксы
    // Заказы
    Route::get('/orders', [AdminController::class, 'getOrders']);
    // Заказы




    Route::delete('/users/{id}/delete', [AdminController::class, 'deleteUser']);
    Route::delete('/items/{id}/delete', [AdminController::class, 'deleteItem']);
});
// Админ


/*
// Товары
Route::middleware(['auth:sanctum'])->get('/items', function () {
    return response()->json(App\Models\Item::all());
});
Route::middleware(['auth:sanctum', 'admin'])->post('/items', [ItemController::class, 'store']);
Route::middleware(['auth:sanctum', 'admin'])->delete('/items/{id}/delete', [AdminController::class, 'deleteItem']);
// Товары

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/categories', [CategoryController::class, 'index']); // Получить все категории
    Route::post('/categories', [CategoryController::class, 'store']); // Добавить новую категорию
});

Route::middleware('auth:api')->get('/order-history', [OrderController::class, 'getOrderHistory']);
Route::get('/order-items', [OrderItemController::class, 'index']);*/
