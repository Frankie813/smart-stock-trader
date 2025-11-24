<?php

namespace Database\Factories;

use App\Models\Stock;
use Illuminate\Database\Eloquent\Factories\Factory;

class StockFactory extends Factory
{
    protected $model = Stock::class;

    public function definition(): array
    {
        return [
            'symbol' => strtoupper(fake()->unique()->lexify('????')),
            'name' => fake()->company() . ' Inc.',
            'exchange' => fake()->randomElement(['NASDAQ', 'NYSE', 'AMEX']),
            'is_active' => true,
        ];
    }

    /**
     * Popular tech stocks
     */
    public function tech(): static
    {
        return $this->state(fn (array $attributes) => [
            'symbol' => fake()->randomElement(['AAPL', 'MSFT', 'GOOGL', 'AMZN', 'NVDA']),
            'name' => match($attributes['symbol']) {
                'AAPL' => 'Apple Inc.',
                'MSFT' => 'Microsoft Corporation',
                'GOOGL' => 'Alphabet Inc.',
                'AMZN' => 'Amazon.com Inc.',
                'NVDA' => 'NVIDIA Corporation',
                default => fake()->company(),
            },
            'exchange' => 'NASDAQ',
        ]);
    }

    /**
     * Inactive stock
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
