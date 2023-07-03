<?php

namespace Database\Factories;

use App\Models\Cooperative;
use App\Models\ModelMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cooperative>
 */
class CooperativeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'slug' => 'fake-' . $this->faker->slug, // This is the super admin user id
            'user_id' => User::inRandomOrder()->first()->id, // This is the super admin user id
            'name' => $this->faker->company,
            'about' => $this->faker->paragraph,
            'address' => $this->faker->address,
            'phone' => $this->faker->phoneNumber,
            'email' => $this->faker->companyEmail,
            'website' => $this->faker->domainName,
            'classification' => $this->faker->randomElement(Cooperative::$classifications),
            'state' => $this->faker->city,
            'lga' => $this->faker->city,
            'verified' => [true, false, true][rand(0, 2)],
            'is_active' => [true, false, true][rand(0, 2)],
        ];
    }

    /**
     * Configure the model factory.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (Cooperative $cooperative) {
            // ...
        })->afterCreating(function (Cooperative $cooperative) {
            $users = User::where('username', 'like', '%fake-%')->get();
            $users->push($cooperative->user);
            $users->push(User::whereUsername('superadmin')->first());

            $users->each(function ($user, $key) use ($cooperative) {
                $data = [
                    'user_id' => $user->id,
                    'model_id' => $cooperative->id,
                ];
                if ($user->username === 'superadmin' || $user->username == $cooperative->user->username) {
                    $data['abilities'] = (new Cooperative())->permissions;
                }
                if ($user->username === 'superadmin' || $key % 2 === 0 || $user->username == $cooperative->user->username) {
                    $data['accepted'] = true;
                    $data['requesting'] = false;
                }
                ModelMember::factory(1)->create($data);
            });
        });
    }
}
