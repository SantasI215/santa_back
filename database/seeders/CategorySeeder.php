<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $categories = [
            ['name' => 'Подарки'],
            ['name' => 'Электроника и гаджеты'],
            ['name' => 'Одежда и аксессуары'],
            ['name' => 'Дом и уют'],
            ['name' => 'Новогодние товары'],
            ['name' => 'Игрушки и сувениры'],
            ['name' => 'Еда и напитки'],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
