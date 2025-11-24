<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_id',
        'date',
        'open',
        'high',
        'low',
        'close',
        'volume',
        'adjusted_close',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'open' => 'decimal:4',
            'high' => 'decimal:4',
            'low' => 'decimal:4',
            'close' => 'decimal:4',
            'volume' => 'integer',
            'adjusted_close' => 'decimal:4',
        ];
    }

    /**
     * Get the stock this price belongs to
     */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    /**
     * Calculate daily return percentage
     */
    public function getDailyReturnAttribute()
    {
        $previousPrice = static::where('stock_id', $this->stock_id)
            ->where('date', '<', $this->date)
            ->orderBy('date', 'desc')
            ->first();

        if (!$previousPrice) {
            return null;
        }

        return (($this->close - $previousPrice->close) / $previousPrice->close) * 100;
    }

    /**
     * Calculate intraday range percentage
     */
    public function getIntradayRangeAttribute()
    {
        return (($this->high - $this->low) / $this->close) * 100;
    }

    /**
     * Check if price went up from previous day
     */
    public function wentUp()
    {
        $previousPrice = static::where('stock_id', $this->stock_id)
            ->where('date', '<', $this->date)
            ->orderBy('date', 'desc')
            ->first();

        if (!$previousPrice) {
            return null;
        }

        return $this->close > $previousPrice->close;
    }
}
