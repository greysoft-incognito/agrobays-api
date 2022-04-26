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
        Plan::where('id', '!=', NULL)->delete();
        Plan::insert([
            [
                'slug' => $faker->slug(),
                'title' => '300 Starter Plan',
                'description' => 'This 300 Starter plan helps you save ₦300 daily for 30days. You get a ₦9,000 foodbag in return.',
                'amount' => 9000.00,
                'icon' => 'fas apple-alt',
                'created_at' => \Carbon\Carbon::now(),
            ], [
                'slug' => $faker->slug(),
                'title' => '500 Twale Plan',
                'description' => 'With the 500 Twale plan, You get a foodbag of ₦15,000 in 30days for saving ₦500 daily.',
                'amount' => 15000.00,
                'icon' => 'fas hotdog',
                'created_at' => \Carbon\Carbon::now(),
            ], [
                'slug' => $faker->slug(),
                'title' => '1000 Oga Plan',
                'description' => 'The 1000 Oga plan will land you ₦30,000 worth of foodbag for saving ₦1000 daily for 30days.',
                'amount' => 30000.00,
                'icon' => 'fas wine-bottle',
                'created_at' => \Carbon\Carbon::now(),
            ]
        ]);
    }
}
