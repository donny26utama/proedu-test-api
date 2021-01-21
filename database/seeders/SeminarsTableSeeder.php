<?php

namespace Database\Seeders;

use App\Models\Seminar;
use Faker\Factory;
use Illuminate\Database\Seeder;

class SeminarsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Seminar::truncate();

        $faker = Factory::create();

        for ($i = 0; $i < 10; $i++) {
            Seminar::create([
                'title' => $faker->sentence,
                'uuid' => $faker->uuid,
                'amount' => $faker->numberBetween(0, 999999),
            ]);
        }
    }
}
