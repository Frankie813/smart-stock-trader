<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8">
            <flux:heading size="xl" class="mb-2">Compare Results</flux:heading>
            <flux:text>Select up to 4 experiments to compare performance side-by-side</flux:text>
        </div>

        @if(session('error'))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                {{ session('error') }}
            </div>
        @endif

        {{-- Selection Section --}}
        <div class="mb-8 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
            <div class="mb-4 flex items-center justify-between">
                <flux:heading size="lg">
                    Select Experiments
                    <flux:badge color="blue" size="sm" class="ml-2">{{ count($selectedExperiments) }}/4</flux:badge>
                </flux:heading>
                @if(count($selectedExperiments) > 0)
                    <flux:button wire:click="clearSelection" variant="ghost" size="sm">
                        Clear Selection
                    </flux:button>
                @endif
            </div>

            @if($completedExperiments->count() > 0)
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach($completedExperiments as $experiment)
                        <div
                            wire:click="toggleExperiment({{ $experiment->id }})"
                            class="cursor-pointer rounded-lg border p-4 transition @if(in_array($experiment->id, $selectedExperiments)) border-blue-500 bg-blue-50 dark:border-blue-400 dark:bg-blue-900/20 @else border-zinc-200 hover:bg-zinc-50 dark:border-zinc-700 dark:hover:bg-zinc-800 @endif"
                        >
                            <div class="mb-2 flex items-center justify-between">
                                <flux:text class="font-semibold">{{ $experiment->name ?? 'Experiment #' . $experiment->id }}</flux:text>
                                @if(in_array($experiment->id, $selectedExperiments))
                                    <svg class="size-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                            </div>
                            <flux:text class="mb-2 text-sm text-zinc-600 dark:text-zinc-400">{{ $experiment->configuration?->name }}</flux:text>
                            @if($experiment->total_return !== null)
                                <flux:text class="text-sm font-semibold {{ $experiment->total_return > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                    {{ $experiment->total_return > 0 ? '+' : '' }}{{ number_format($experiment->total_return, 2) }}%
                                </flux:text>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <div class="rounded-lg border border-zinc-200 p-8 text-center dark:border-zinc-700">
                    <flux:text class="text-zinc-500">No completed experiments to compare</flux:text>
                </div>
            @endif
        </div>

        {{-- Comparison Table --}}
        @if(count($selectedExperimentsData) >= 2)
            <div class="mb-8 rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <flux:heading size="lg" class="mb-4">Comparison</flux:heading>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Metric</th>
                                @foreach($selectedExperimentsData as $exp)
                                    <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                        {{ $exp->name ?? 'Exp #' . $exp->id }}
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <flux:text class="font-medium">Configuration</flux:text>
                                </td>
                                @foreach($selectedExperimentsData as $exp)
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <flux:text>{{ $exp->configuration?->name ?? 'N/A' }}</flux:text>
                                    </td>
                                @endforeach
                            </tr>

                            <tr>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <flux:text class="font-medium">Total Return %</flux:text>
                                </td>
                                @php
                                    $maxReturn = $selectedExperimentsData->max('total_return');
                                @endphp
                                @foreach($selectedExperimentsData as $exp)
                                    <td class="whitespace-nowrap px-4 py-3 {{ $exp->total_return == $maxReturn ? 'bg-green-50 dark:bg-green-900/20' : '' }}">
                                        <flux:text class="font-semibold {{ $exp->total_return > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $exp->total_return > 0 ? '+' : '' }}{{ number_format($exp->total_return ?? 0, 2) }}%
                                        </flux:text>
                                    </td>
                                @endforeach
                            </tr>

                            <tr>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <flux:text class="font-medium">Win Rate %</flux:text>
                                </td>
                                @php
                                    $maxWinRate = $selectedExperimentsData->max('win_rate');
                                @endphp
                                @foreach($selectedExperimentsData as $exp)
                                    <td class="whitespace-nowrap px-4 py-3 @if($exp->win_rate == $maxWinRate) bg-green-50 dark:bg-green-900/20 @endif">
                                        <flux:text>{{ number_format($exp->win_rate ?? 0, 1) }}%</flux:text>
                                    </td>
                                @endforeach
                            </tr>

                            <tr>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <flux:text class="font-medium">Total Trades</flux:text>
                                </td>
                                @foreach($selectedExperimentsData as $exp)
                                    <td class="whitespace-nowrap px-4 py-3">
                                        <flux:text>{{ number_format($exp->backtestResults->first()?->total_trades ?? 0) }}</flux:text>
                                    </td>
                                @endforeach
                            </tr>

                            <tr>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <flux:text class="font-medium">Sharpe Ratio</flux:text>
                                </td>
                                @php
                                    $maxSharpe = $selectedExperimentsData->max('sharpe_ratio');
                                @endphp
                                @foreach($selectedExperimentsData as $exp)
                                    <td class="whitespace-nowrap px-4 py-3 @if($exp->sharpe_ratio == $maxSharpe) bg-green-50 dark:bg-green-900/20 @endif">
                                        <flux:text>{{ number_format($exp->sharpe_ratio ?? 0, 2) }}</flux:text>
                                    </td>
                                @endforeach
                            </tr>

                            <tr>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <flux:text class="font-medium">Max Drawdown %</flux:text>
                                </td>
                                @php
                                    $minDrawdown = $selectedExperimentsData->min(function($exp) {
                                        return abs($exp->backtestResults->first()?->max_drawdown ?? 0);
                                    });
                                @endphp
                                @foreach($selectedExperimentsData as $exp)
                                    @php
                                        $drawdown = $exp->backtestResults->first()?->max_drawdown ?? 0;
                                    @endphp
                                    <td class="whitespace-nowrap px-4 py-3 @if(abs($drawdown) == $minDrawdown) bg-green-50 dark:bg-green-900/20 @endif">
                                        <flux:text class="text-red-600 dark:text-red-400">{{ number_format($drawdown, 2) }}%</flux:text>
                                    </td>
                                @endforeach
                            </tr>

                            <tr>
                                <td class="whitespace-nowrap px-4 py-3">
                                    <flux:text class="font-medium">Profit Factor</flux:text>
                                </td>
                                @php
                                    $maxProfitFactor = $selectedExperimentsData->max(function($exp) {
                                        return $exp->backtestResults->first()?->profit_factor ?? 0;
                                    });
                                @endphp
                                @foreach($selectedExperimentsData as $exp)
                                    @php
                                        $profitFactor = $exp->backtestResults->first()?->profit_factor ?? 0;
                                    @endphp
                                    <td class="whitespace-nowrap px-4 py-3 @if($profitFactor == $maxProfitFactor) bg-green-50 dark:bg-green-900/20 @endif">
                                        <flux:text>{{ number_format($profitFactor, 2) }}</flux:text>
                                    </td>
                                @endforeach
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Winner Badge --}}
                @php
                    $winner = $selectedExperimentsData->sortByDesc(function($exp) {
                        $result = $exp->backtestResults->first();
                        return $result ? $result->risk_adjusted_score ?? 0 : 0;
                    })->first();
                @endphp
                @if($winner)
                    <div class="mt-6 rounded-lg bg-yellow-50 p-4 text-center dark:bg-yellow-900/20">
                        <flux:text class="text-lg font-semibold">
                            🏆 Winner: {{ $winner->name ?? 'Experiment #' . $winner->id }}
                        </flux:text>
                        <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                            Based on risk-adjusted performance
                        </flux:text>
                    </div>
                @endif
            </div>
        @elseif(count($selectedExperiments) === 1)
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-8 text-center dark:border-blue-800 dark:bg-blue-900/20">
                <flux:text class="text-blue-800 dark:text-blue-400">Select at least one more experiment to compare</flux:text>
            </div>
        @endif
    </div>
</div>
