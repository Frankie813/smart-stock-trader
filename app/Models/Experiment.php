<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Experiment extends Model
{
    use HasFactory;

    protected $fillable = [
        'model_configuration_id',
        'stock_ids',
        'name',
        'start_date',
        'end_date',
        'initial_capital',
        'status',
        'progress',
        'results',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'stock_ids' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'initial_capital' => 'decimal:2',
            'progress' => 'integer',
            'results' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * Get the configuration used for this experiment
     */
    public function configuration(): BelongsTo
    {
        return $this->belongsTo(ModelConfiguration::class, 'model_configuration_id');
    }

    /**
     * Get all backtest results for this experiment
     */
    public function backtestResults(): HasMany
    {
        return $this->hasMany(BacktestResult::class);
    }

    /**
     * Get all predictions made during this experiment
     */
    public function predictions(): HasMany
    {
        return $this->hasMany(Prediction::class);
    }

    /**
     * Check if experiment is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if experiment is running
     */
    public function isRunning(): bool
    {
        return $this->status === 'running';
    }

    /**
     * Check if experiment failed
     */
    public function hasFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get duration of experiment
     */
    public function getDurationAttribute()
    {
        if (! $this->started_at || ! $this->completed_at) {
            return null;
        }

        return $this->started_at->diffInSeconds($this->completed_at);
    }

    /**
     * Get overall total return
     */
    public function getTotalReturnAttribute()
    {
        return $this->results['overall']['total_return'] ?? null;
    }

    /**
     * Get overall win rate
     */
    public function getWinRateAttribute()
    {
        return $this->results['overall']['win_rate'] ?? null;
    }

    /**
     * Get overall Sharpe ratio
     */
    public function getSharpeRatioAttribute()
    {
        return $this->results['overall']['avg_sharpe_ratio'] ?? null;
    }

    /**
     * Mark experiment as started
     */
    public function markAsStarted()
    {
        $this->update([
            'status' => 'running',
            'progress' => 0,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark experiment as completed
     */
    public function markAsCompleted(array $results)
    {
        $this->update([
            'status' => 'completed',
            'progress' => 100,
            'results' => $results,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark experiment as failed
     */
    public function markAsFailed(string $errorMessage)
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    /**
     * Update progress
     */
    public function updateProgress(int $progress)
    {
        $this->update(['progress' => min($progress, 100)]);
    }

    /**
     * Scope to get completed experiments
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get running experiments
     */
    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }
}
