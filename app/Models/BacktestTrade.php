<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BacktestTrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'backtest_result_id',
        'stock_id',
        'entry_date',
        'exit_date',
        'entry_price',
        'exit_price',
        'shares',
        'prediction',
        'actual_direction',
        'was_correct',
        'profit_loss',
        'return_percentage',
        'confidence_score',
        'commission',
        'exit_reason',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'exit_date' => 'date',
            'entry_price' => 'decimal:4',
            'exit_price' => 'decimal:4',
            'shares' => 'integer',
            'was_correct' => 'boolean',
            'profit_loss' => 'decimal:2',
            'return_percentage' => 'decimal:4',
            'confidence_score' => 'decimal:4',
            'commission' => 'decimal:2',
        ];
    }

    /**
     * Get the backtest result this trade belongs to
     */
    public function backtestResult(): BelongsTo
    {
        return $this->belongsTo(BacktestResult::class);
    }

    /**
     * Get the stock this trade is for
     */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    /**
     * Check if trade was profitable
     */
    public function isProfitable(): bool
    {
        return $this->profit_loss > 0;
    }

    /**
     * Check if trade was a loss
     */
    public function isLoss(): bool
    {
        return $this->profit_loss < 0;
    }

    /**
     * Get holding period in days
     */
    public function getHoldingPeriodAttribute()
    {
        return $this->entry_date->diffInDays($this->exit_date);
    }

    /**
     * Get total invested amount
     */
    public function getTotalInvestedAttribute()
    {
        return $this->entry_price * $this->shares;
    }

    /**
     * Get total return amount
     */
    public function getTotalReturnAttribute()
    {
        return $this->exit_price * $this->shares;
    }

    /**
     * Get net profit/loss after commission
     */
    public function getNetProfitLossAttribute()
    {
        return $this->profit_loss - $this->commission;
    }

    /**
     * Check if prediction was bullish
     */
    public function wasBullish(): bool
    {
        return $this->prediction === 'up';
    }

    /**
     * Check if prediction was bearish
     */
    public function wasBearish(): bool
    {
        return $this->prediction === 'down';
    }

    /**
     * Get exit reason display text
     */
    public function getExitReasonTextAttribute()
    {
        return match($this->exit_reason) {
            'eod' => 'End of Day',
            'stop_loss' => 'Stop Loss',
            'take_profit' => 'Take Profit',
            default => 'Unknown',
        };
    }

    /**
     * Scope to get winning trades
     */
    public function scopeWinning($query)
    {
        return $query->where('profit_loss', '>', 0);
    }

    /**
     * Scope to get losing trades
     */
    public function scopeLosing($query)
    {
        return $query->where('profit_loss', '<', 0);
    }

    /**
     * Scope to get correct predictions
     */
    public function scopeCorrect($query)
    {
        return $query->where('was_correct', true);
    }

    /**
     * Scope to get incorrect predictions
     */
    public function scopeIncorrect($query)
    {
        return $query->where('was_correct', false);
    }

    /**
     * Scope to get trades exited by stop loss
     */
    public function scopeStopLoss($query)
    {
        return $query->where('exit_reason', 'stop_loss');
    }

    /**
     * Scope to get trades exited by take profit
     */
    public function scopeTakeProfit($query)
    {
        return $query->where('exit_reason', 'take_profit');
    }
}
