<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\category;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Medicine>
 */
class MedicineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
 public function definition(): array
    {
        return [
            'name_en' => $this->faker->word(),
            'name_ar' => $this->faker->word(),
            'pharmacy_price' => $this->faker->randomFloat(2, 5, 50),
            'consumer_price' => $this->faker->randomFloat(2, 10, 100),
            'discount' => $this->faker->optional()->randomFloat(2, 0, 20),
            'barcode' => $this->faker->ean13(),
            'composition' => $this->faker->sentence(),
            'stock_quantity' => $this->faker->numberBetween(10, 500),             'expiry_date' => $this->faker->dateTimeBetween('+1 months', '+2 years'),
            'manufacturer' => $this->faker->company(),
            'country_of_origin' => $this->faker->country(),
            'needs_prescription' => $this->faker->boolean(),
            'category_id' => Category::inRandomOrder()->first()?->id ?? Category::factory(),
'image' => $this->faker->image(
    storage_path('app/public/images'), // مكان الحفظ
    640, 480, // أبعاد الصورة
    null,     // فئة الصورة (مثلاً animals أو people)
    false     // ترجع فقط اسم الملف
),
         ];
    }
}
