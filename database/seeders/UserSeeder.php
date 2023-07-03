<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $rand = rand(0, 30);

        if (User::whereEmail('admin@greysoft.ng')->whereUsername('superadmin')->doesntExist()) {
            User::factory(1)->create([
                'firstname' => 'Default',
                'lastname' => 'Super Admin',
                'username' => 'superadmin',
                'role' => 'admin',
                'email' => 'admin@greysoft.ng',
            ]);
        }

        User::factory(10)->create([
            'created_at' => now()->subDays($rand),
            'updated_at' => now()->subDays($rand),
        ]);
    }
}
