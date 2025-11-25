<div>
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8">
            <flux:heading size="xl" class="mb-2">
                {{ $configuration?->exists ? 'Edit Configuration' : 'Create Configuration' }}
            </flux:heading>
            <flux:text>Configure your machine learning model and trading strategy</flux:text>
        </div>

        <form wire:submit="save">
            <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                {{-- Tabs Navigation --}}
                <div class="border-b border-zinc-200 dark:border-zinc-700">
                    <div class="flex space-x-8 px-6">
                        <button
                            type="button"
                            wire:click="$set('currentTab', 'basic')"
                            class="border-b-2 py-4 text-sm font-medium transition @if($currentTab === 'basic') border-blue-500 text-blue-600 dark:text-blue-400 @else border-transparent text-zinc-600 hover:border-zinc-300 hover:text-zinc-800 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-200 @endif"
                        >
                            Basic Info
                        </button>
                        <button
                            type="button"
                            wire:click="$set('currentTab', 'model')"
                            class="border-b-2 py-4 text-sm font-medium transition @if($currentTab === 'model') border-blue-500 text-blue-600 dark:text-blue-400 @else border-transparent text-zinc-600 hover:border-zinc-300 hover:text-zinc-800 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-200 @endif"
                        >
                            Model Settings
                        </button>
                        <button
                            type="button"
                            wire:click="$set('currentTab', 'features')"
                            class="border-b-2 py-4 text-sm font-medium transition @if($currentTab === 'features') border-blue-500 text-blue-600 dark:text-blue-400 @else border-transparent text-zinc-600 hover:border-zinc-300 hover:text-zinc-800 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-200 @endif"
                        >
                            Features
                            <flux:badge color="blue" size="sm" class="ml-2">{{ $enabledFeaturesCount }}</flux:badge>
                        </button>
                        <button
                            type="button"
                            wire:click="$set('currentTab', 'trading')"
                            class="border-b-2 py-4 text-sm font-medium transition @if($currentTab === 'trading') border-blue-500 text-blue-600 dark:text-blue-400 @else border-transparent text-zinc-600 hover:border-zinc-300 hover:text-zinc-800 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-200 @endif"
                        >
                            Trading Rules
                        </button>
                    </div>
                </div>

                {{-- Tab Content --}}
                <div class="p-6">
                    {{-- Basic Info Tab --}}
                    @if($currentTab === 'basic')
                        <div class="space-y-6">
                            <flux:field>
                                <flux:label>Configuration Name</flux:label>
                                <flux:input wire:model="name" type="text" placeholder="e.g., Aggressive Strategy" />
                                <flux:error name="name" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Description</flux:label>
                                <flux:textarea wire:model="description" placeholder="Describe your strategy..." rows="4" />
                                <flux:error name="description" />
                            </flux:field>
                        </div>
                    @endif

                    {{-- Model Settings Tab --}}
                    @if($currentTab === 'model')
                        <div class="space-y-6">
                            <flux:field>
                                <flux:label>Number of Estimators (Trees)</flux:label>
                                <flux:input wire:model="hyperparameters.n_estimators" type="number" min="50" max="300" step="10" />
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    Number of trees in the ensemble. More trees = better accuracy but slower training (50-300)
                                </flux:text>
                                <flux:error name="hyperparameters.n_estimators" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Maximum Tree Depth</flux:label>
                                <flux:input wire:model="hyperparameters.max_depth" type="number" min="1" max="15" step="1" />
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    Maximum depth of each tree. Higher = more complex patterns but risks overfitting (1-15)
                                </flux:text>
                                <flux:error name="hyperparameters.max_depth" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Learning Rate</flux:label>
                                <flux:input wire:model="hyperparameters.learning_rate" type="number" min="0.001" max="1" step="0.001" />
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    Step size for optimization. Lower = more accurate but slower convergence (0.001-1)
                                </flux:text>
                                <flux:error name="hyperparameters.learning_rate" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Subsample Ratio</flux:label>
                                <flux:input wire:model="hyperparameters.subsample" type="number" min="0.5" max="1" step="0.1" />
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    Percentage of samples used per tree. Lower values prevent overfitting (0.5-1)
                                </flux:text>
                                <flux:error name="hyperparameters.subsample" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Column Sample by Tree</flux:label>
                                <flux:input wire:model="hyperparameters.colsample_bytree" type="number" min="0.5" max="1" step="0.1" />
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    Percentage of features used per tree. Lower adds randomness and prevents overfitting (0.5-1)
                                </flux:text>
                                <flux:error name="hyperparameters.colsample_bytree" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Train/Test Split Ratio</flux:label>
                                <flux:input wire:model="hyperparameters.train_test_split" type="number" min="0.5" max="0.95" step="0.05" />
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    Percentage of data used for training. 80% is standard (0.5-0.95)
                                </flux:text>
                                <flux:error name="hyperparameters.train_test_split" />
                            </flux:field>
                        </div>
                    @endif

                    {{-- Features Tab --}}
                    @if($currentTab === 'features')
                        <div class="space-y-6">
                            <div class="mb-4 flex items-center justify-between">
                                <div>
                                    <flux:text class="font-medium">{{ $enabledFeaturesCount }} features selected</flux:text>
                                    @error('features')
                                        <flux:text class="text-sm text-red-600 dark:text-red-400">{{ $message }}</flux:text>
                                    @enderror
                                </div>
                                <div class="flex gap-2">
                                    <flux:button type="button" wire:click="selectAllFeatures" variant="ghost" size="sm">
                                        Select All
                                    </flux:button>
                                    <flux:button type="button" wire:click="deselectAllFeatures" variant="ghost" size="sm">
                                        Deselect All
                                    </flux:button>
                                </div>
                            </div>

                            {{-- Trend Indicators --}}
                            <div>
                                <flux:heading size="lg" class="mb-3">Trend Indicators</flux:heading>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <flux:checkbox wire:model="features.sma_10">SMA 10-day</flux:checkbox>
                                    <flux:checkbox wire:model="features.sma_50">SMA 50-day</flux:checkbox>
                                    <flux:checkbox wire:model="features.sma_200">SMA 200-day</flux:checkbox>
                                    <flux:checkbox wire:model="features.ema_12">EMA 12-day</flux:checkbox>
                                    <flux:checkbox wire:model="features.ema_26">EMA 26-day</flux:checkbox>
                                    <flux:checkbox wire:model="features.macd_line">MACD Line</flux:checkbox>
                                    <flux:checkbox wire:model="features.macd_signal">MACD Signal</flux:checkbox>
                                    <flux:checkbox wire:model="features.macd_histogram">MACD Histogram</flux:checkbox>
                                </div>
                            </div>

                            {{-- Momentum Indicators --}}
                            <div>
                                <flux:heading size="lg" class="mb-3">Momentum Indicators</flux:heading>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <flux:checkbox wire:model="features.rsi_7">RSI 7-day</flux:checkbox>
                                    <flux:checkbox wire:model="features.rsi_14">RSI 14-day</flux:checkbox>
                                    <flux:checkbox wire:model="features.rsi_21">RSI 21-day</flux:checkbox>
                                    <flux:checkbox wire:model="features.stoch_k">Stochastic %K</flux:checkbox>
                                    <flux:checkbox wire:model="features.stoch_d">Stochastic %D</flux:checkbox>
                                </div>
                            </div>

                            {{-- Volatility Indicators --}}
                            <div>
                                <flux:heading size="lg" class="mb-3">Volatility Indicators</flux:heading>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <flux:checkbox wire:model="features.bb_upper">Bollinger Upper Band</flux:checkbox>
                                    <flux:checkbox wire:model="features.bb_middle">Bollinger Middle Band</flux:checkbox>
                                    <flux:checkbox wire:model="features.bb_lower">Bollinger Lower Band</flux:checkbox>
                                    <flux:checkbox wire:model="features.bb_width">Bollinger Band Width</flux:checkbox>
                                    <flux:checkbox wire:model="features.atr">ATR (Average True Range)</flux:checkbox>
                                </div>
                            </div>

                            {{-- Volume Indicators --}}
                            <div>
                                <flux:heading size="lg" class="mb-3">Volume Indicators</flux:heading>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <flux:checkbox wire:model="features.volume_ratio">Volume Ratio</flux:checkbox>
                                    <flux:checkbox wire:model="features.obv">OBV (On-Balance Volume)</flux:checkbox>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Trading Rules Tab --}}
                    @if($currentTab === 'trading')
                        <div class="space-y-6">
                            <flux:field>
                                <flux:label>Stop Loss %</flux:label>
                                <flux:input wire:model="trading_rules.stop_loss_pct" type="number" min="0" max="50" step="0.1" />
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    Exit position if loss exceeds this percentage (0-50)
                                </flux:text>
                                <flux:error name="trading_rules.stop_loss_pct" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Take Profit %</flux:label>
                                <flux:input wire:model="trading_rules.take_profit_pct" type="number" min="0" max="100" step="0.1" />
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    Exit position when profit reaches this percentage (0-100)
                                </flux:text>
                                <flux:error name="trading_rules.take_profit_pct" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Confidence Threshold</flux:label>
                                <flux:input wire:model="trading_rules.confidence_threshold" type="number" min="0.5" max="1" step="0.05" />
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    Only execute trades when model confidence exceeds this threshold (0.5-1)
                                </flux:text>
                                <flux:error name="trading_rules.confidence_threshold" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Commission per Trade ($)</flux:label>
                                <flux:input wire:model="trading_rules.commission_per_trade" type="number" min="0" max="100" step="0.1" />
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    Trading fees applied to each trade (0-100)
                                </flux:text>
                                <flux:error name="trading_rules.commission_per_trade" />
                            </flux:field>

                            <flux:field>
                                <flux:label>Max Position Size %</flux:label>
                                <flux:input wire:model="trading_rules.max_position_size_pct" type="number" min="10" max="100" step="5" />
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                                    Maximum percentage of capital to use per trade (10-100)
                                </flux:text>
                                <flux:error name="trading_rules.max_position_size_pct" />
                            </flux:field>
                        </div>
                    @endif
                </div>

                {{-- Form Actions --}}
                <div class="flex items-center justify-between border-t border-zinc-200 px-6 py-4 dark:border-zinc-700">
                    <flux:button type="button" wire:navigate href="{{ route('configurations.index') }}" variant="ghost">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        {{ $configuration?->exists ? 'Update Configuration' : 'Create Configuration' }}
                    </flux:button>
                </div>
            </div>
        </form>
    </div>
</div>
