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
        $names = [
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
        ];

        $name = $names[rand(0, 9)];

        return [
            'name' => $name,
            'slug' => str($name)->slug()->append('-'.rand(1000, 9999)),
            'category' => [
                'breakfast',
                'lunch',
                'dinner',
            ][rand(0, 2)],
            'description' => $this->faker->paragraph(),
            'calories' => rand(100, 1000),
            'protein' => rand(10, 100),
            'carbohydrates' => rand(10, 100),
            'fat' => rand(10, 100),
        ];
    }
}
