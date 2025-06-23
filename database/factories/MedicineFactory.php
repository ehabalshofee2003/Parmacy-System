<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\category;
use Illuminate\Process\FakeProcessDescription;
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
            'barcode' => $this->faker->unique()->ean13(),
            'category_id' => Category::inRandomOrder()->first()?->id ?? Category::factory(),
            'image_url' => $this->faker->imageUrl(200, 200),
            'manufacturer' => $this->faker->company(),
            'pharmacy_price' => $this->faker->randomFloat(2, 1, 50),
            'consumer_price' => $this->faker->randomFloat(2, 10, 100),
            'discount' => $this->faker->optional()->randomFloat(2, 0, 30),
            'stock_quantity' => $this->faker->numberBetween(0, 200),
            'expiry_date' => $this->faker->dateTimeBetween('now', '+2 years')->format('Y-m-d'),
            'composition' => $this->faker->sentence(),
            'needs_prescription' => $this->faker->boolean(30),
            'reorder_level' => 10,
            'admin_id' => null,
        ];
    }
}
