<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
          'name' => $this->faker->word(),
'image' => $this->faker->image(
    storage_path('app/public/images'), // مكان الحفظ
    640, 480, // أبعاد الصورة
    null,     // فئة الصورة (مثلاً animals أو people)
    false     // ترجع فقط اسم الملف
),
        ];
    }
}
