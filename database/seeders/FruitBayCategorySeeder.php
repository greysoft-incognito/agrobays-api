<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\FruitBayCategory;

class FruitBayCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        FruitBayCategory::insert([
            [
                'slug' => 'citrus',
                'title' => 'Citrus',
                'description' => 'Citrus fruits includes important crops such as oranges, lemons, grapefruits, pomelos, and limes.',
                'image' => 'public/uploads/images/1013334087_2019582335.png',
            ], [
                'slug' => 'berries',
                'title' => 'Berries',
                'description' => 'Small, juicy fruits with thin skins
                Highly perishable
                Some berries include: blackberries, cranberries, blueberries, red and black raspberries, strawberries, and grapes.',
                'image' => 'public/uploads/images/1455791222_799698722.png',
            ], [
                'slug' => 'tropical',
                'title' => 'Tropical',
                'description' => 'Grown in warm climatesand are considered tobe somewhat exotic
                Available throughout the world
                Some tropical fruits include: avocados, coconut, bananas, figs, dates, guavas, mangoes, papayas, pineapples, pomegranates, and kiwi',
                'image' => 'public/uploads/images/1886411736_955619728.png',
            ], [
                'slug' => 'vegitables',
                'title' => 'Vegitables',
                'description' => 'Vegitables are beautiful food staples',
                'image' => 'public/uploads/images/896311396_1718470427.png',
            ],
        ]);
    }
}