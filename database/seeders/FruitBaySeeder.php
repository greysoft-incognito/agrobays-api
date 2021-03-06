<?php

namespace Database\Seeders;

use App\Models\FruitBay;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FruitBaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if (file_exists($path = base_path('database/seeders/fruit_bays.sql'))) {
            $sql = file_get_contents($path);
            DB::unprepared($sql);
        } else {
            FruitBay::insert([
            ]);
        }
    }
}
