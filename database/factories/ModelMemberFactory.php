<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ModelMember>
 */
class ModelMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $accepted = [true, false, true][rand(0, 2)];
        $requesting = $accepted ? $accepted : [true, false, false][rand(0, 2)];

        $abilities = str('manage_members,manage_plans,manage_admins,manage_settings,update_profile')
                        ->explode(',')->shuffle()->slice(0, rand(0, 5))->__toString();

        return [
            'user_id' => 1,
            'model_type' => 'App\Models\Cooperative',
            'model_id' => 1,
            'accepted' => $accepted,
            'requesting' => $requesting,
            'abilities' => ! $requesting ? $abilities : null,
        ];
    }
}
