<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BoxProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $data = [
            ['box_id' => 1, 'item_id' => 3, 'quantity' => 2],
            ['box_id' => 1, 'item_id' => 5, 'quantity' => 1],
            ['box_id' => 1, 'item_id' => 8, 'quantity' => 3],
            ['box_id' => 2, 'item_id' => 2, 'quantity' => 5],
            ['box_id' => 2, 'item_id' => 7, 'quantity' => 2],
            ['box_id' => 2, 'item_id' => 10, 'quantity' => 1],
            ['box_id' => 3, 'item_id' => 1, 'quantity' => 4],
            ['box_id' => 3, 'item_id' => 4, 'quantity' => 2],
            ['box_id' => 3, 'item_id' => 12, 'quantity' => 3],
            ['box_id' => 3, 'item_id' => 15, 'quantity' => 1],
            ['box_id' => 4, 'item_id' => 6, 'quantity' => 2],
            ['box_id' => 4, 'item_id' => 9, 'quantity' => 4],
            ['box_id' => 4, 'item_id' => 11, 'quantity' => 1],
            ['box_id' => 4, 'item_id' => 13, 'quantity' => 3],
            ['box_id' => 5, 'item_id' => 14, 'quantity' => 2],
            ['box_id' => 5, 'item_id' => 18, 'quantity' => 5],
            ['box_id' => 5, 'item_id' => 20, 'quantity' => 1],
            ['box_id' => 5, 'item_id' => 21, 'quantity' => 2],
            ['box_id' => 6, 'item_id' => 16, 'quantity' => 3],
            ['box_id' => 6, 'item_id' => 17, 'quantity' => 2],
            ['box_id' => 6, 'item_id' => 19, 'quantity' => 4],
            ['box_id' => 6, 'item_id' => 22, 'quantity' => 1],
            ['box_id' => 6, 'item_id' => 23, 'quantity' => 2],
        ];

        DB::table('box_product')->insert($data);
    }
}
