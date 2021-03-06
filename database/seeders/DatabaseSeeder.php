<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory()->count(1)->create();
        $this->call([
            UserSeeder::class,
            PlanSeeder::class,
            FoodBagSeeder::class,
            FoodSeeder::class,
            FruitBayCategorySeeder::class,
            FruitBaySeeder::class,
        ]);
    }
}
