<?php

namespace Database\Seeders;

use App\Models\Food;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Faker\Generator;

class FoodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(Generator $faker)
    {
        Food::truncate();
        Food::insert([
            [
                'food_bag_id' => 1,
                'name' => 'Rice',
                'description' => $faker->text(),
                'weight' => '50kg',
            ], [
                'food_bag_id' => 1,
                'name' => 'Spagetti',
                'description' => $faker->text(),
                'weight' => '10kg',
            ], [
                'food_bag_id' => 2,
                'name' => 'Beans',
                'description' => $faker->text(),
                'weight' => '50kg',
            ], [
                'food_bag_id' => 2,
                'name' => 'Semolina',
                'description' => $faker->text(),
                'weight' => '10kg',
            ]
        ]);
    }
}