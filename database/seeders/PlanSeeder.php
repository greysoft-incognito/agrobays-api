<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Plan;
use Faker\Generator;


class PlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run(Generator $faker)
    {
        Plan::truncate();
        Plan::insert([
            [
                'slug' => $faker->slug(),
                'title' => '300 Basic',
                'description' => $faker->text(),
                'amount' => 300.00,
                'icon' => 'fas apple-alt',
            ], [
                'slug' => $faker->slug(),
                'title' => '500 Premium',
                'description' => $faker->text(),
                'amount' => 500.00,
                'icon' => 'fas hotdog',
            ], [
                'slug' => $faker->slug(),
                'title' => '1000 Platinum',
                'description' => $faker->text(),
                'amount' => 1000.00,
                'icon' => 'fas wine-bottle',
            ]
        ]);
    }
}
