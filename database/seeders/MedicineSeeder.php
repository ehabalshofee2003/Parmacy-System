<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Medicine;
use App\Models\category;
class MedicineSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    public function run(): void
    {
Category::factory(5)->create(); // أولاً توليد أصناف
Medicine::factory(50)->create(); // توليد أدوية مرتبطة بها

    }
}
