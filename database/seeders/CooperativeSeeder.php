<?php

namespace Database\Seeders;

use App\Models\Cooperative;
use Illuminate\Database\Seeder;

class CooperativeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Cooperative::query()->delete();
        // // Disable foreign key checks!
        // \DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        // Cooperative::truncate();
        // \DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        Cooperative::factory(5)->create();
    }
}
