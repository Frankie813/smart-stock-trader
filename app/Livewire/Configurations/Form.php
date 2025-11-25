<?php

namespace App\Livewire\Configurations;

use App\Models\ModelConfiguration;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class Form extends Component
{
    public ?ModelConfiguration $configuration = null;

    public string $name = '';

    public string $description = '';

    public string $currentTab = 'basic';

    public array $hyperparameters = [
        'n_estimators' => 100,
        'max_depth' => 5,
        'learning_rate' => 0.1,
        'subsample' => 0.8,
        'colsample_bytree' => 0.8,
        'train_test_split' => 0.8,
    ];

    public array $features = [];

    public array $trading_rules = [
        'stop_loss_pct' => 2.0,
        'take_profit_pct' => 5.0,
        'confidence_threshold' => 0.6,
        'commission_per_trade' => 1.0,
        'max_position_size_pct' => 100,
    ];

    public function mount(?ModelConfiguration $configuration = null): void
    {
        if ($configuration && $configuration->exists) {
            $this->configuration = $configuration;
            $this->name = $configuration->name;
            $this->description = $configuration->description ?? '';
            $this->hyperparameters = array_merge($this->hyperparameters, $configuration->hyperparameters ?? []);
            $this->features = $configuration->features_enabled ?? [];
            $this->trading_rules = array_merge($this->trading_rules, $configuration->trading_rules ?? []);
        } else {
            $this->initializeDefaultFeatures();
        }
    }

    protected function initializeDefaultFeatures(): void
    {
        $defaultFeatures = [
            'sma_10' => false,
            'sma_50' => false,
            'sma_200' => false,
            'ema_12' => false,
            'ema_26' => false,
            'macd_line' => false,
            'macd_signal' => false,
            'macd_histogram' => false,
            'rsi_7' => false,
            'rsi_14' => true,
            'rsi_21' => false,
            'stoch_k' => false,
            'stoch_d' => false,
            'bb_upper' => false,
            'bb_middle' => false,
            'bb_lower' => false,
            'bb_width' => false,
            'atr' => false,
            'volume_ratio' => false,
            'obv' => false,
        ];

        $this->features = array_merge($defaultFeatures, $this->features);
    }

    public function selectAllFeatures(): void
    {
        foreach ($this->features as $key => $value) {
            $this->features[$key] = true;
        }
    }

    public function deselectAllFeatures(): void
    {
        foreach ($this->features as $key => $value) {
            $this->features[$key] = false;
        }
    }

    public function save(): void
    {
        $this->validate([
            'name' => [
                'required',
                'min:3',
                'max:255',
                Rule::unique('model_configurations', 'name')->ignore($this->configuration?->id),
            ],
            'description' => 'nullable|string|max:500',
            'hyperparameters.n_estimators' => 'required|integer|min:50|max:300',
            'hyperparameters.max_depth' => 'required|integer|min:1|max:15',
            'hyperparameters.learning_rate' => 'required|numeric|min:0.001|max:1',
            'hyperparameters.subsample' => 'required|numeric|min:0.5|max:1',
            'hyperparameters.colsample_bytree' => 'required|numeric|min:0.5|max:1',
            'hyperparameters.train_test_split' => 'required|numeric|min:0.5|max:0.95',
            'trading_rules.stop_loss_pct' => 'required|numeric|min:0|max:50',
            'trading_rules.take_profit_pct' => 'required|numeric|min:0|max:100',
            'trading_rules.confidence_threshold' => 'required|numeric|min:0.5|max:1',
            'trading_rules.commission_per_trade' => 'required|numeric|min:0|max:100',
            'trading_rules.max_position_size_pct' => 'required|numeric|min:10|max:100',
        ]);

        $enabledCount = count(array_filter($this->features));
        if ($enabledCount < 5) {
            $this->addError('features', 'At least 5 features must be selected');

            return;
        }

        $data = [
            'name' => $this->name,
            'description' => $this->description,
            'hyperparameters' => $this->hyperparameters,
            'features_enabled' => $this->features,
            'trading_rules' => $this->trading_rules,
        ];

        if ($this->configuration && $this->configuration->exists) {
            $this->configuration->update($data);
            session()->flash('success', 'Configuration updated successfully!');
        } else {
            ModelConfiguration::create($data);
            session()->flash('success', 'Configuration created successfully!');
        }

        $this->redirect(route('configurations.index'), navigate: true);
    }

    public function render(): View
    {
        $enabledFeaturesCount = count(array_filter($this->features));

        return view('livewire.configurations.form', [
            'enabledFeaturesCount' => $enabledFeaturesCount,
        ]);
    }
}
