<?php

namespace Database\Seeders;

use App\Models\Cooperative;
use App\Models\ModelMember;
use App\Models\User;
use Illuminate\Database\Seeder;

class ModelMemberSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $users = User::where('username', 'like', '%fake-%')->get();

        $users->each(function ($user) {
            $model = Cooperative::inRandomOrder()->first();

            ModelMember::factory(1)->create([
                'user_id' => $user->id,
                'model_id' => $model->id,
            ]);
        });
    }
}
