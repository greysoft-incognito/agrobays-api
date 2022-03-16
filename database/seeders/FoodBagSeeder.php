<?php

namespace Database\Seeders;

use App\Models\Food;
use App\Models\FoodBag;
use App\Models\Plan;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Generator;

class FoodBagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(Generator $faker)
    {
        FoodBag::where('id', '!=', NULL)->delete();
        FoodBag::insert([
            [
                'plan_id' => Plan::first()->id??1,
                'title' => 'Bag 1',
                'description' => $faker->text(),
            ], [
                'plan_id' => Plan::get()->reverse()->first()->id??3,
                'title' => 'Bag 4',
                'description' => $faker->text(),
            ],
        ]);
    }
}
