<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModelConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'hyperparameters',
        'features_enabled',
        'trading_rules',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'hyperparameters' => 'array',
            'features_enabled' => 'array',
            'trading_rules' => 'array',
            'is_default' => 'boolean',
        ];
    }

    /**
     * Get all experiments using this configuration
     */
    public function experiments(): HasMany
    {
        return $this->hasMany(Experiment::class);
    }

    /**
     * Get all backtest results using this configuration
     */
    public function backtestResults(): HasMany
    {
        return $this->hasMany(BacktestResult::class);
    }

    /**
     * Get the count of enabled features
     */
    public function getEnabledFeaturesCountAttribute()
    {
        return count(array_filter($this->features_enabled ?? []));
    }

    /**
     * Get list of enabled feature names
     */
    public function getEnabledFeaturesListAttribute()
    {
        return array_keys(array_filter($this->features_enabled ?? []));
    }

    /**
     * Get average performance across all experiments
     */
    public function getAveragePerformance()
    {
        $completedExperiments = $this->experiments()
            ->where('status', 'completed')
            ->get();

        if ($completedExperiments->isEmpty()) {
            return null;
        }

        return [
            'avg_return' => $completedExperiments->avg('results.overall.total_return'),
            'avg_win_rate' => $completedExperiments->avg('results.overall.win_rate'),
            'total_experiments' => $completedExperiments->count(),
        ];
    }

    /**
     * Scope to get default configuration
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Set this configuration as the default
     */
    public function setAsDefault()
    {
        // Remove default flag from all other configurations
        static::where('id', '!=', $this->id)->update(['is_default' => false]);
        
        // Set this one as default
        $this->update(['is_default' => true]);
    }
}
