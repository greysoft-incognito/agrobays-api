<?php

namespace Database\Seeders;

use App\Models\FoodBag;
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
                'plan_id' => 1,
                'title' => 'Food Bag A',
                'description' => 'Foodbag packed and designed to make sure you maintain a healthy feeding routine.',
                'created_at' => \Carbon\Carbon::now(),
            ], [
                'plan_id' => 1,
                'title' => 'Food Bag B',
                'description' => 'Foodbag packed to ensure you maintain a healthy feeding routine.',
                'created_at' => \Carbon\Carbon::now(),
            ], [
                'plan_id' => 1,
                'title' => 'Food Bag C',
                'description' => 'Foodbag packed to ensure you have a healthy feeding routine.',
                'created_at' => \Carbon\Carbon::now(),
            ], [
                'plan_id' => 2,
                'title' => 'Food Bag A',
                'description' => 'Specially Packed for your Nourishment.',
                'created_at' => \Carbon\Carbon::now(),
            ], [
                'plan_id' => 2,
                'title' => 'Food Bag B',
                'description' => 'Specially Packed for your Nourishment.',
                'created_at' => \Carbon\Carbon::now(),
            ], [
                'plan_id' => 2,
                'title' => 'Food Bag C',
                'description' => 'Specially Packed for your Nourishment.',
                'created_at' => \Carbon\Carbon::now(),
            ],[
                'plan_id' => 3,
                'title' => 'Food Bag A',
                'description' => 'Specially Packed for your daily routine Nourishment.',
                'created_at' => \Carbon\Carbon::now(),
            ], [
                'plan_id' => 3,
                'title' => 'Food Bag B',
                'description' => 'Specially Packed for your daily routine Nourishment.',
                'created_at' => \Carbon\Carbon::now(),
            ], [
                'plan_id' => 3,
                'title' => 'Food Bag C',
                'description' => 'Specially Packed for your daily routine Nourishment.',
                'created_at' => \Carbon\Carbon::now(),
            ],
        ]);
    }
}