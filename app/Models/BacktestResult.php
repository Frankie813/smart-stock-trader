<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BacktestResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'experiment_id',
        'stock_id',
        'model_configuration_id',
        'start_date',
        'end_date',
        'initial_capital',
        'final_capital',
        'total_return',
        'total_trades',
        'winning_trades',
        'losing_trades',
        'win_rate',
        'total_profit_loss',
        'accuracy_percentage',
        'sharpe_ratio',
        'max_drawdown',
        'avg_profit_per_trade',
        'avg_loss_per_trade',
        'profit_factor',
        'largest_win',
        'largest_loss',
        'model_version',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'initial_capital' => 'decimal:2',
            'final_capital' => 'decimal:2',
            'total_return' => 'decimal:2',
            'total_trades' => 'integer',
            'winning_trades' => 'integer',
            'losing_trades' => 'integer',
            'win_rate' => 'decimal:2',
            'total_profit_loss' => 'decimal:2',
            'accuracy_percentage' => 'decimal:2',
            'sharpe_ratio' => 'decimal:4',
            'max_drawdown' => 'decimal:2',
            'avg_profit_per_trade' => 'decimal:2',
            'avg_loss_per_trade' => 'decimal:2',
            'profit_factor' => 'decimal:4',
            'largest_win' => 'decimal:2',
            'largest_loss' => 'decimal:2',
        ];
    }

    /**
     * Get the experiment this result belongs to
     */
    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }

    /**
     * Get the stock this result is for (null if overall)
     */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    /**
     * Get the configuration used
     */
    public function configuration(): BelongsTo
    {
        return $this->belongsTo(ModelConfiguration::class, 'model_configuration_id');
    }

    /**
     * Get all trades for this result
     */
    public function trades(): HasMany
    {
        return $this->hasMany(BacktestTrade::class);
    }

    /**
     * Check if this is an overall result (across all stocks)
     */
    public function isOverall(): bool
    {
        return is_null($this->stock_id);
    }

    /**
     * Check if the backtest was profitable
     */
    public function isProfitable(): bool
    {
        return $this->total_return > 0;
    }

    /**
     * Get profit or loss amount
     */
    public function getProfitLossAmountAttribute()
    {
        return $this->final_capital - $this->initial_capital;
    }

    /**
     * Get annualized return (approximate)
     */
    public function getAnnualizedReturnAttribute()
    {
        $days = $this->start_date->diffInDays($this->end_date);
        
        if ($days === 0) {
            return 0;
        }

        $years = $days / 365;
        
        return (pow(1 + ($this->total_return / 100), 1 / $years) - 1) * 100;
    }

    /**
     * Get risk-adjusted return score
     */
    public function getRiskAdjustedScoreAttribute()
    {
        if (!$this->sharpe_ratio || !$this->max_drawdown) {
            return null;
        }

        // Higher Sharpe + Lower Drawdown = Better score
        return ($this->sharpe_ratio * 10) - abs($this->max_drawdown);
    }

    /**
     * Scope to get profitable results
     */
    public function scopeProfitable($query)
    {
        return $query->where('total_return', '>', 0);
    }

    /**
     * Scope to get unprofitable results
     */
    public function scopeUnprofitable($query)
    {
        return $query->where('total_return', '<=', 0);
    }

    /**
     * Scope to get overall results (not stock-specific)
     */
    public function scopeOverall($query)
    {
        return $query->whereNull('stock_id');
    }

    /**
     * Scope to get stock-specific results
     */
    public function scopeForStock($query)
    {
        return $query->whereNotNull('stock_id');
    }
}
