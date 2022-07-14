<?php

namespace Database\Seeders;

use App\Models\Food;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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

        if (file_exists($path = base_path('database/seeders/food.sql'))) {
            $sql = file_get_contents($path);
            DB::unprepared($sql);
        } else {
            Food::insert([
                [
                    'food_bag_id' => 1,
                    'name' => 'Rice',
                    'description' => $faker->text(),
                    'weight' => '50kg',
                    'created_at' => \Carbon\Carbon::now(),
                ], [
                    'food_bag_id' => 1,
                    'name' => 'Spagetti',
                    'description' => $faker->text(),
                    'weight' => '10kg',
                    'created_at' => \Carbon\Carbon::now(),
                ], [
                    'food_bag_id' => 2,
                    'name' => 'Beans',
                    'description' => $faker->text(),
                    'weight' => '50kg',
                    'created_at' => \Carbon\Carbon::now(),
                ], [
                    'food_bag_id' => 2,
                    'name' => 'Semolina',
                    'description' => $faker->text(),
                    'weight' => '10kg',
                    'created_at' => \Carbon\Carbon::now(),
                ],
            ]);
        }
    }
}
