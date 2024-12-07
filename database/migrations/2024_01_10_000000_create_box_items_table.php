<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('box_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('box_id')->constrained('order_items')->onDelete('cascade');
            $table->foreignId('item_id')->constrained('items')->onDelete('cascade');
            $table->integer('quantity');
            $table->enum('status', ['Добавлен', 'Не добавлен'])->default('Не добавлен');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('box_items');
    }
};
