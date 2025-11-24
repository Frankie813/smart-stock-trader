<?php

namespace Database\Seeders;

use App\Models\ModelConfiguration;
use Illuminate\Database\Seeder;

class ModelConfigurationSeeder extends Seeder
{
    public function run(): void
    {
        // Conservative Strategy
        ModelConfiguration::updateOrCreate(
            ['name' => 'Conservative Strategy'],
            [
                'description' => 'Low-risk approach with strict stop losses and high confidence threshold',
                'hyperparameters' => [
                    'n_estimators' => 100,
                    'max_depth' => 3,
                    'learning_rate' => 0.05,
                    'subsample' => 0.8,
                    'colsample_bytree' => 0.8,
                    'min_child_weight' => 3,
                    'gamma' => 1,
                    'train_test_split' => 0.8,
                ],
                'features_enabled' => [
                    'sma_10' => false,
                    'sma_50' => true,
                    'sma_200' => true,
                    'ema_12' => false,
                    'ema_26' => false,
                    'rsi_7' => false,
                    'rsi_14' => true,
                    'rsi_21' => false,
                    'macd' => true,
                    'macd_signal' => true,
                    'macd_histogram' => false,
                    'bb_upper' => true,
                    'bb_middle' => true,
                    'bb_lower' => true,
                    'bb_width' => false,
                    'atr' => true,
                    'volume_ratio' => true,
                    'obv' => false,
                    'stochastic_k' => false,
                    'stochastic_d' => false,
                ],
                'trading_rules' => [
                    'stop_loss_percent' => 2.0,
                    'take_profit_percent' => 3.0,
                    'confidence_threshold' => 0.65,
                    'commission_per_trade' => 1.0,
                    'max_position_size_percent' => 100,
                ],
                'is_default' => true,
            ]
        );

        // Aggressive Strategy
        ModelConfiguration::updateOrCreate(
            ['name' => 'Aggressive Strategy'],
            [
                'description' => 'Higher risk with more features and lower confidence threshold',
                'hyperparameters' => [
                    'n_estimators' => 200,
                    'max_depth' => 7,
                    'learning_rate' => 0.1,
                    'subsample' => 0.9,
                    'colsample_bytree' => 0.9,
                    'min_child_weight' => 1,
                    'gamma' => 0,
                    'train_test_split' => 0.8,
                ],
                'features_enabled' => [
                    'sma_10' => true,
                    'sma_50' => true,
                    'sma_200' => true,
                    'ema_12' => true,
                    'ema_26' => true,
                    'rsi_7' => true,
                    'rsi_14' => true,
                    'rsi_21' => true,
                    'macd' => true,
                    'macd_signal' => true,
                    'macd_histogram' => true,
                    'bb_upper' => true,
                    'bb_middle' => true,
                    'bb_lower' => true,
                    'bb_width' => true,
                    'atr' => true,
                    'volume_ratio' => true,
                    'obv' => true,
                    'stochastic_k' => true,
                    'stochastic_d' => true,
                ],
                'trading_rules' => [
                    'stop_loss_percent' => 5.0,
                    'take_profit_percent' => 10.0,
                    'confidence_threshold' => 0.50,
                    'commission_per_trade' => 1.0,
                    'max_position_size_percent' => 100,
                ],
                'is_default' => false,
            ]
        );

        // Balanced Strategy
        ModelConfiguration::updateOrCreate(
            ['name' => 'Balanced Strategy'],
            [
                'description' => 'Middle-ground approach balancing risk and reward',
                'hyperparameters' => [
                    'n_estimators' => 150,
                    'max_depth' => 5,
                    'learning_rate' => 0.08,
                    'subsample' => 0.85,
                    'colsample_bytree' => 0.85,
                    'min_child_weight' => 2,
                    'gamma' => 0.5,
                    'train_test_split' => 0.8,
                ],
                'features_enabled' => [
                    'sma_10' => true,
                    'sma_50' => true,
                    'sma_200' => false,
                    'ema_12' => true,
                    'ema_26' => true,
                    'rsi_7' => false,
                    'rsi_14' => true,
                    'rsi_21' => false,
                    'macd' => true,
                    'macd_signal' => true,
                    'macd_histogram' => true,
                    'bb_upper' => true,
                    'bb_middle' => true,
                    'bb_lower' => true,
                    'bb_width' => true,
                    'atr' => true,
                    'volume_ratio' => true,
                    'obv' => false,
                    'stochastic_k' => false,
                    'stochastic_d' => false,
                ],
                'trading_rules' => [
                    'stop_loss_percent' => 3.0,
                    'take_profit_percent' => 6.0,
                    'confidence_threshold' => 0.58,
                    'commission_per_trade' => 1.0,
                    'max_position_size_percent' => 100,
                ],
                'is_default' => false,
            ]
        );
    }
}
