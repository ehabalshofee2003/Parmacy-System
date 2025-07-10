<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\category;
use App\Models\Medicine;
use App\Models\supply;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
public function run(): void
{
     Medicine::factory(10)->create();
    Supply::factory(10)->create();
}

}
