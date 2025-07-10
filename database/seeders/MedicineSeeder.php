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
     Medicine::factory(10)->create(); // توليد أدوية مرتبطة بها

    }
}
