<?php

namespace Database\Factories;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MealPlan>
 */
class MealPlanFactory extends Factory
{
    use WithoutModelEvents;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'name' => [
                'Roasted Chicken and Veggies',
                'Grilled Salmon with Avocado Salsa',
                'Pasta with Tomato Sauce',
                'Fried Rice with Tofu',
                'Shrimp and Broccoli Stir Fry',
                'Baked Chicken Breast',
                'Salmon with Lemon and Capers',
                'Chicken Burrito Bowls',
                'Turkey Chili',
                'Chicken Parmesan',
            ][rand(0, 9)],
            'slug' => [
                'roasted-chicken-and-veggies',
                'grilled-salmon-with-avocado-salsa',
                'pasta-with-tomato-sauce',
                'fried-rice-with-tofu',
                'shrimp-and-broccoli-stir-fry',
                'baked-chicken-breast',
                'salmon-with-lemon-and-capers',
                'chicken-burrito-bowls',
                'turkey-chili',
                'chicken-parmesan',
            ][rand(0, 9)] . '-' . rand(1000, 9999),
            'image' => $this->faker->imageUrl(),
            'category' => [
                'breakfast',
                'lunch',
                'dinner',
            ][rand(0, 3)],
            'description' => $this->faker->paragraph(),
            'calories' => rand(100, 1000),
            'protein' => rand(10, 100),
            'carbohydrates' => rand(10, 100),
            'fat' => rand(10, 100),
        ];
    }
}