<div>
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8">
            <flux:heading size="xl" class="mb-2">Run New Experiment</flux:heading>
            <flux:text>Configure and execute a new backtesting experiment</flux:text>
        </div>

        <form wire:submit="runExperiment">
            {{-- Configuration Section --}}
            <div class="mb-8 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">1. Select Configuration</flux:heading>

                <flux:field>
                    <flux:label>Model Configuration</flux:label>
                    <flux:select wire:model.live="configurationId">
                        <option value="">Choose a configuration...</option>
                        @foreach($configurations as $config)
                            <option value="{{ $config->id }}">{{ $config->name }}</option>
                        @endforeach
                    </flux:select>
                    <flux:error name="configurationId" />
                </flux:field>

                @if($selectedConfiguration)
                    <div class="mt-4 rounded-lg bg-zinc-50 p-4 dark:bg-zinc-800">
                        <flux:text class="mb-2 text-sm font-medium">Configuration Preview:</flux:text>
                        <div class="grid gap-2 text-sm">
                            <div class="flex items-center justify-between">
                                <flux:text class="text-zinc-600 dark:text-zinc-400">Features:</flux:text>
                                <flux:badge color="blue" size="sm">{{ $selectedConfiguration->enabled_features_count }}</flux:badge>
                            </div>
                            <div class="flex items-center justify-between">
                                <flux:text class="text-zinc-600 dark:text-zinc-400">Estimators:</flux:text>
                                <flux:text>{{ $selectedConfiguration->hyperparameters['n_estimators'] ?? 'N/A' }}</flux:text>
                            </div>
                            <div class="flex items-center justify-between">
                                <flux:text class="text-zinc-600 dark:text-zinc-400">Learning Rate:</flux:text>
                                <flux:text>{{ $selectedConfiguration->hyperparameters['learning_rate'] ?? 'N/A' }}</flux:text>
                            </div>
                        </div>
                        <div class="mt-3">
                            <flux:button type="button" wire:navigate href="{{ route('configurations.create') }}" variant="ghost" size="sm">
                                Create New Configuration
                            </flux:button>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Stocks Section --}}
            <div class="mb-8 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-4 flex items-center justify-between">
                    <flux:heading size="lg">2. Select Stocks</flux:heading>
                    <div class="flex gap-2">
                        <flux:button type="button" wire:click="selectAllStocks" variant="ghost" size="sm">
                            Select All
                        </flux:button>
                        <flux:button type="button" wire:click="deselectAllStocks" variant="ghost" size="sm">
                            Deselect All
                        </flux:button>
                    </div>
                </div>

                @error('selectedStocks')
                    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                        {{ $message }}
                    </div>
                @enderror

                @if($stocks->count() > 0)
                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach($stocks as $stock)
                            <div
                                wire:click="toggleStock({{ $stock->id }})"
                                class="cursor-pointer rounded-lg border p-4 transition @if(in_array($stock->id, $selectedStocks)) border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20 @else border-zinc-200 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800 @endif"
                            >
                                <div class="flex items-center justify-between">
                                    <div>
                                        <flux:text class="font-semibold">{{ $stock->symbol }}</flux:text>
                                        <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">{{ $stock->name }}</flux:text>
                                    </div>
                                    @if(in_array($stock->id, $selectedStocks))
                                        <svg class="size-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-lg border border-zinc-200 p-8 text-center dark:border-zinc-700">
                        <flux:text class="mb-2">No active stocks found</flux:text>
                        <flux:button type="button" wire:navigate href="{{ route('stocks.create') }}" variant="primary" size="sm">
                            Add Stock
                        </flux:button>
                    </div>
                @endif
            </div>

            {{-- Date Range Section --}}
            <div class="mb-8 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">3. Date Range</flux:heading>

                <div class="grid gap-4 sm:grid-cols-2">
                    <flux:field>
                        <flux:label>Start Date</flux:label>
                        <flux:input wire:model.live="startDate" type="date" />
                        <flux:error name="startDate" />
                    </flux:field>

                    <flux:field>
                        <flux:label>End Date</flux:label>
                        <flux:input wire:model.live="endDate" type="date" />
                        <flux:error name="endDate" />
                    </flux:field>
                </div>

                @if($tradingDays > 0)
                    <div class="mt-4 rounded-lg bg-blue-50 p-3 dark:bg-blue-900/20">
                        <flux:text class="text-sm text-blue-800 dark:text-blue-400">
                            <strong>{{ number_format($tradingDays) }}</strong> days in selected range
                        </flux:text>
                    </div>
                @endif
            </div>

            {{-- Capital Section --}}
            <div class="mb-8 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">4. Initial Capital</flux:heading>

                <flux:field>
                    <flux:label>Capital Amount ($)</flux:label>
                    <flux:input wire:model="initialCapital" type="number" min="100" max="1000000" step="100" />
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                        Starting capital for backtest simulation ($100 - $1,000,000)
                    </flux:text>
                    <flux:error name="initialCapital" />
                </flux:field>
            </div>

            {{-- Actions --}}
            <div class="flex items-center justify-between">
                <flux:button type="button" wire:navigate href="{{ route('experiments.index') }}" variant="ghost">
                    Cancel
                </flux:button>
                <flux:button type="submit" variant="primary" size="base">
                    <svg class="mr-2 size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Run Experiment
                </flux:button>
            </div>
        </form>
    </div>
</div>
