<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'symbol',
        'name',
        'exchange',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get all price records for this stock
     */
    public function prices(): HasMany
    {
        return $this->hasMany(StockPrice::class);
    }

    /**
     * Get all predictions for this stock
     */
    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class);
    }

    /**
     * Get all backtest results for this stock
     */
    public function backtestResults(): HasMany
    {
        return $this->hasMany(BacktestResult::class);
    }

    /**
     * Get all backtest trades for this stock
     */
    public function backtestTrades(): HasMany
    {
        return $this->hasMany(BacktestTrade::class);
    }

    /**
     * Get the latest price for this stock
     */
    public function latestPrice()
    {
        return $this->prices()->latest('date')->first();
    }

    /**
     * Get prices within a date range
     */
    public function pricesInRange($startDate, $endDate)
    {
        return $this->prices()
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();
    }

    /**
     * Get the most recent prediction accuracy
     */
    public function getRecentAccuracy($days = 30)
    {
        return $this->predictions()
            ->whereNotNull('was_correct')
            ->where('prediction_date', '>=', now()->subDays($days))
            ->avg('was_correct') * 100;
    }
}
