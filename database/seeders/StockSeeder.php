<?php

namespace Database\Seeders;

use App\Models\Stock;
use Illuminate\Database\Seeder;

class StockSeeder extends Seeder
{
    public function run(): void
    {
        $stocks = [
            [
                'symbol' => 'AAPL',
                'name' => 'Apple Inc.',
                'exchange' => 'NASDAQ',
                'is_active' => true,
            ],
            [
                'symbol' => 'MSFT',
                'name' => 'Microsoft Corporation',
                'exchange' => 'NASDAQ',
                'is_active' => true,
            ],
            [
                'symbol' => 'TSLA',
                'name' => 'Tesla, Inc.',
                'exchange' => 'NASDAQ',
                'is_active' => true,
            ],
            [
                'symbol' => 'NVDA',
                'name' => 'NVIDIA Corporation',
                'exchange' => 'NASDAQ',
                'is_active' => true,
            ],
            [
                'symbol' => 'GOOGL',
                'name' => 'Alphabet Inc.',
                'exchange' => 'NASDAQ',
                'is_active' => true,
            ],
            [
                'symbol' => 'AMZN',
                'name' => 'Amazon.com, Inc.',
                'exchange' => 'NASDAQ',
                'is_active' => true,
            ],
            [
                'symbol' => 'META',
                'name' => 'Meta Platforms, Inc.',
                'exchange' => 'NASDAQ',
                'is_active' => true,
            ],
            [
                'symbol' => 'AMD',
                'name' => 'Advanced Micro Devices, Inc.',
                'exchange' => 'NASDAQ',
                'is_active' => true,
            ],
        ];

        foreach ($stocks as $stock) {
            Stock::updateOrCreate(
                ['symbol' => $stock['symbol']],
                $stock
            );
        }
    }
}
