<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        {{-- Header --}}
        <div class="mb-8 flex items-center justify-between">
            <div>
                <div class="mb-2 flex items-center gap-3">
                    <flux:heading size="xl">{{ $experiment->name ?? 'Experiment #' . $experiment->id }}</flux:heading>
                    @if($experiment->status === 'completed')
                        <flux:badge color="green">Completed</flux:badge>
                    @elseif($experiment->status === 'running')
                        <flux:badge color="yellow">Running</flux:badge>
                    @elseif($experiment->status === 'failed')
                        <flux:badge color="red">Failed</flux:badge>
                    @else
                        <flux:badge color="zinc">{{ ucfirst($experiment->status) }}</flux:badge>
                    @endif
                </div>
                <div class="flex items-center gap-4 text-sm text-zinc-600 dark:text-zinc-400">
                    <flux:text>Configuration: <strong>{{ $experiment->configuration?->name }}</strong></flux:text>
                    <flux:text>•</flux:text>
                    <flux:text>{{ $experiment->start_date?->format('M d, Y') }} - {{ $experiment->end_date?->format('M d, Y') }}</flux:text>
                </div>
            </div>
            <div class="flex gap-2">
                <flux:button wire:navigate href="{{ route('experiments.create', ['config' => $experiment->model_configuration_id]) }}" variant="ghost">
                    Run Similar
                </flux:button>
                <flux:button wire:navigate href="{{ route('results.index') }}" variant="ghost">
                    Compare
                </flux:button>
            </div>
        </div>

        @if($overallResult)
            {{-- Primary Metrics Cards --}}
            <div class="mb-8 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="mb-2 text-sm text-zinc-600 dark:text-zinc-400">Total Return</flux:text>
                    <flux:heading size="2xl" class="font-bold {{ $overallResult->total_return > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $overallResult->total_return > 0 ? '+' : '' }}{{ number_format($overallResult->total_return, 2) }}%
                    </flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $overallResult->total_profit_loss > 0 ? '+' : '' }}${{ number_format($overallResult->total_profit_loss, 2) }}
                    </flux:text>
                </div>

                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="mb-2 text-sm text-zinc-600 dark:text-zinc-400">Win Rate</flux:text>
                    <flux:heading size="2xl" class="font-bold">{{ number_format($overallResult->win_rate, 1) }}%</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ $overallResult->winning_trades }}W / {{ $overallResult->losing_trades }}L
                    </flux:text>
                </div>

                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="mb-2 text-sm text-zinc-600 dark:text-zinc-400">Total Trades</flux:text>
                    <flux:heading size="2xl" class="font-bold">{{ number_format($overallResult->total_trades) }}</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        {{ number_format($overallResult->accuracy_percentage, 1) }}% accuracy
                    </flux:text>
                </div>

                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="mb-2 text-sm text-zinc-600 dark:text-zinc-400">Sharpe Ratio</flux:text>
                    <flux:heading size="2xl" class="font-bold">{{ number_format($overallResult->sharpe_ratio ?? 0, 2) }}</flux:heading>
                    <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                        Risk-adjusted return
                    </flux:text>
                </div>
            </div>

            {{-- Capital & Risk Metrics --}}
            <div class="mb-8">
                <flux:heading size="lg" class="mb-4">Capital & Risk Analysis</flux:heading>
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-5">
                    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:text class="mb-1 text-xs text-zinc-600 dark:text-zinc-400">Initial Capital</flux:text>
                        <flux:text class="text-lg font-semibold">${{ number_format($overallResult->initial_capital ?? 0, 2) }}</flux:text>
                    </div>

                    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:text class="mb-1 text-xs text-zinc-600 dark:text-zinc-400">Final Capital</flux:text>
                        <flux:text class="text-lg font-semibold {{ $overallResult->final_capital > $overallResult->initial_capital ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            ${{ number_format($overallResult->final_capital ?? 0, 2) }}
                        </flux:text>
                    </div>

                    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:text class="mb-1 text-xs text-zinc-600 dark:text-zinc-400">Max Drawdown</flux:text>
                        <flux:text class="text-lg font-semibold text-red-600 dark:text-red-400">{{ number_format($overallResult->max_drawdown ?? 0, 2) }}%</flux:text>
                    </div>

                    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:text class="mb-1 text-xs text-zinc-600 dark:text-zinc-400">Largest Win</flux:text>
                        <flux:text class="text-lg font-semibold text-green-600 dark:text-green-400">${{ number_format($overallResult->largest_win ?? 0, 2) }}</flux:text>
                    </div>

                    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:text class="mb-1 text-xs text-zinc-600 dark:text-zinc-400">Largest Loss</flux:text>
                        <flux:text class="text-lg font-semibold text-red-600 dark:text-red-400">${{ number_format($overallResult->largest_loss ?? 0, 2) }}</flux:text>
                    </div>
                </div>
            </div>

            {{-- Performance Metrics --}}
            <div class="mb-8">
                <flux:heading size="lg" class="mb-4">Performance Metrics</flux:heading>
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:text class="mb-1 text-xs text-zinc-600 dark:text-zinc-400">Profit Factor</flux:text>
                        <flux:text class="text-lg font-semibold">{{ number_format($overallResult->profit_factor ?? 0, 2) }}</flux:text>
                        <flux:text class="mt-1 text-xs text-zinc-500 dark:text-zinc-500">
                            {{ $overallResult->profit_factor > 1 ? 'Profitable' : 'Unprofitable' }}
                        </flux:text>
                    </div>

                    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:text class="mb-1 text-xs text-zinc-600 dark:text-zinc-400">Avg Win</flux:text>
                        <flux:text class="text-lg font-semibold text-green-600 dark:text-green-400">${{ number_format($overallResult->avg_profit_per_trade ?? 0, 2) }}</flux:text>
                    </div>

                    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:text class="mb-1 text-xs text-zinc-600 dark:text-zinc-400">Avg Loss</flux:text>
                        <flux:text class="text-lg font-semibold text-red-600 dark:text-red-400">${{ number_format($overallResult->avg_loss_per_trade ?? 0, 2) }}</flux:text>
                    </div>

                    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
                        <flux:text class="mb-1 text-xs text-zinc-600 dark:text-zinc-400">Risk/Reward Ratio</flux:text>
                        <flux:text class="text-lg font-semibold">
                            @php
                                $avgLoss = abs($overallResult->avg_loss_per_trade ?? 0);
                                $riskReward = $avgLoss > 0 ? ($overallResult->avg_profit_per_trade ?? 0) / $avgLoss : 0;
                            @endphp
                            {{ number_format($riskReward, 2) }}
                        </flux:text>
                    </div>
                </div>
            </div>

            {{-- Model & Experiment Info --}}
            <div class="mb-8">
                <flux:heading size="lg" class="mb-4">Model & Experiment Details</flux:heading>
                <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <flux:text class="mb-1 text-xs text-zinc-600 dark:text-zinc-400">Model Version</flux:text>
                            <flux:text class="font-medium">{{ $overallResult->model_version ?? 'N/A' }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="mb-1 text-xs text-zinc-600 dark:text-zinc-400">Duration</flux:text>
                            <flux:text class="font-medium">
                                @if($experiment->started_at && $experiment->completed_at)
                                    {{ $experiment->started_at->diffForHumans($experiment->completed_at, true) }}
                                @else
                                    N/A
                                @endif
                            </flux:text>
                        </div>
                        <div>
                            <flux:text class="mb-1 text-xs text-zinc-600 dark:text-zinc-400">Started</flux:text>
                            <flux:text class="font-medium">{{ $experiment->started_at?->format('M d, Y H:i') ?? 'N/A' }}</flux:text>
                        </div>
                        <div>
                            <flux:text class="mb-1 text-xs text-zinc-600 dark:text-zinc-400">Completed</flux:text>
                            <flux:text class="font-medium">{{ $experiment->completed_at?->format('M d, Y H:i') ?? 'N/A' }}</flux:text>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Per-Stock Performance --}}
        @if($stockResults->count() > 0)
            <div class="mb-8">
                <flux:heading size="lg" class="mb-4">Performance by Stock</flux:heading>
                <div class="overflow-x-auto rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-800">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Stock</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Return</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">P&L</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Trades</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Win Rate</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Accuracy</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Sharpe</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Max DD</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Profit Factor</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Largest Win</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Largest Loss</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                            @foreach($stockResults as $result)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <flux:text class="font-medium">{{ $result->stock?->symbol }}</flux:text>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <flux:text class="font-semibold {{ $result->total_return > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $result->total_return > 0 ? '+' : '' }}{{ number_format($result->total_return, 2) }}%
                                        </flux:text>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <flux:text class="{{ $result->total_profit_loss > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $result->total_profit_loss > 0 ? '+' : '' }}${{ number_format($result->total_profit_loss, 2) }}
                                        </flux:text>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <flux:text>{{ $result->total_trades }}</flux:text>
                                        <flux:text class="text-xs text-zinc-500 dark:text-zinc-500">
                                            ({{ $result->winning_trades }}W/{{ $result->losing_trades }}L)
                                        </flux:text>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <flux:text>{{ number_format($result->win_rate, 1) }}%</flux:text>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <flux:text>{{ number_format($result->accuracy_percentage, 1) }}%</flux:text>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <flux:text>{{ number_format($result->sharpe_ratio ?? 0, 2) }}</flux:text>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <flux:text class="text-red-600 dark:text-red-400">{{ number_format($result->max_drawdown ?? 0, 2) }}%</flux:text>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <flux:text>{{ number_format($result->profit_factor ?? 0, 2) }}</flux:text>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <flux:text class="text-green-600 dark:text-green-400">${{ number_format($result->largest_win ?? 0, 2) }}</flux:text>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4">
                                        <flux:text class="text-red-600 dark:text-red-400">${{ number_format($result->largest_loss ?? 0, 2) }}</flux:text>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        {{-- Recent Trades --}}
        <div class="mb-8">
            <flux:heading size="lg" class="mb-4">Recent Trades</flux:heading>
            @if($recentTrades->count() > 0)
                <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Prediction</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Entry</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Exit</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">P&L</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Correct</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                            @foreach($recentTrades as $trade)
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <flux:text class="text-zinc-600 dark:text-zinc-400">{{ $trade->trade_date?->format('M d, Y') }}</flux:text>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <flux:text class="font-medium">{{ $trade->stock?->symbol }}</flux:text>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <flux:badge color="{{ $trade->prediction === 'up' ? 'green' : 'red' }}" size="sm">
                                            {{ strtoupper($trade->prediction) }}
                                        </flux:badge>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <flux:text>${{ number_format($trade->entry_price, 2) }}</flux:text>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <flux:text>${{ number_format($trade->exit_price, 2) }}</flux:text>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <flux:text class="font-semibold {{ $trade->profit_loss > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            {{ $trade->profit_loss > 0 ? '+' : '' }}${{ number_format($trade->profit_loss, 2) }}
                                        </flux:text>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        @if($trade->was_correct)
                                            <span class="text-green-600 dark:text-green-400">✓</span>
                                        @else
                                            <span class="text-red-600 dark:text-red-400">✗</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6">
                    {{ $recentTrades->links() }}
                </div>
            @else
                <div class="rounded-lg border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-900">
                    <flux:text class="text-zinc-500">No trades available</flux:text>
                </div>
            @endif
        </div>
    </div>
</div>
