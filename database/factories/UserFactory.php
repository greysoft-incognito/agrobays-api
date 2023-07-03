<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $city = $this->faker->city;
        $state = $this->faker->city;
        $gender = ['male', 'female'][rand(0, 1)];
        $name = $this->faker->name($gender);

        return [
            'firstname' => str($name)->beforeLast(' ')->title(),
            'lastname' => str($name)->afterLast(' ')->title(),
            'username' => str($this->faker->userName)->prepend('fake-')->slug(),
            'gender' => $gender,
            'address' => [
                'home' => $this->faker->address,
                'shipping' => $this->faker->address,
            ],
            'country' => [
                'name' => $this->faker->country,
                'iso2' => $this->faker->countryCode,
            ],
            'state' => [
                'name' => $state,
                'iso2' => str($state)->limit(2, '')->upper(),
            ],
            'city' => [
                'name' => $city,
                'iso2' => str($city)->limit(2, '')->upper(),
            ],
            'bank' => json_encode([
                'bank' => $this->faker->company,
                'nuban' => rand(201, 299) . rand(1000000, 9999999),
                'account_name' => $name,
            ]),
            'role' => [
                'dispatch','manager','user','user','user',
            ][rand(0, 4)],
            'email' => $this->faker->unique()->safeEmail,
            'phone' => $this->faker->unique()->e164PhoneNumber,
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return static
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
                'role' => 'admin',
            ];
        });
    }
}
