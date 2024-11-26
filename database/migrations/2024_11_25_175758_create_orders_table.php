<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Связь с таблицей users
            $table->string('address'); // Адрес доставки
            $table->string('payment_method'); // Метод оплаты
            $table->decimal('total_price', 10, 2); // Общая стоимость заказа
            $table->string('status')->default('pending'); // Статус заказа
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
