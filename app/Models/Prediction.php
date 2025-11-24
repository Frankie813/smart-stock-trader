<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prediction extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_id',
        'experiment_id',
        'prediction_date',
        'predicted_direction',
        'confidence_score',
        'actual_direction',
        'was_correct',
        'model_version',
        'features_used',
    ];

    protected function casts(): array
    {
        return [
            'prediction_date' => 'date',
            'confidence_score' => 'decimal:4',
            'was_correct' => 'boolean',
            'features_used' => 'array',
        ];
    }

    /**
     * Get the stock this prediction is for
     */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    /**
     * Get the experiment this prediction belongs to
     */
    public function experiment(): BelongsTo
    {
        return $this->belongsTo(Experiment::class);
    }

    /**
     * Check if prediction was bullish (up)
     */
    public function isBullish(): bool
    {
        return $this->predicted_direction === 'up';
    }

    /**
     * Check if prediction was bearish (down)
     */
    public function isBearish(): bool
    {
        return $this->predicted_direction === 'down';
    }

    /**
     * Check if actual outcome is known
     */
    public function hasActualOutcome(): bool
    {
        return !is_null($this->actual_direction);
    }

    /**
     * Get confidence percentage
     */
    public function getConfidencePercentageAttribute()
    {
        return round($this->confidence_score * 100, 2);
    }

    /**
     * Update with actual outcome
     */
    public function updateActualOutcome(string $direction)
    {
        $this->update([
            'actual_direction' => $direction,
            'was_correct' => $this->predicted_direction === $direction,
        ]);
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
     * Scope to get predictions with actual outcomes
     */
    public function scopeWithActual($query)
    {
        return $query->whereNotNull('actual_direction');
    }

    /**
     * Scope to get high confidence predictions
     */
    public function scopeHighConfidence($query, $threshold = 0.6)
    {
        return $query->where('confidence_score', '>=', $threshold);
    }
}
