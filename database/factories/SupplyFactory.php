<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\category;
/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Supply>
 */
class SupplyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
  public function definition(): array
    {
        return [
            'title' => $this->faker->word(),
            'category_id' => Category::inRandomOrder()->first()?->id ?? Category::factory(),
            'pharmacy_price' => $this->faker->randomFloat(2, 1, 50),
            'consumer_price' => $this->faker->randomFloat(2, 10, 100),
            'discount' => $this->faker->optional()->randomFloat(2, 0, 20),
            'stock_quantity' => $this->faker->numberBetween(5, 100),
            'reorder_level' => 10,
        ];
    }
}
