<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8">
            <flux:heading size="xl" class="mb-2">Dashboard</flux:heading>
            <flux:text>Overview of your stock trading experiments and performance metrics</flux:text>
        </div>

        {{-- Hero Metrics Section --}}
        <div class="mb-8 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            {{-- Total Experiments --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-2 flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">Total Experiments</flux:text>
                    <svg class="size-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <flux:heading size="2xl" class="font-bold">{{ number_format($totalExperiments) }}</flux:heading>
            </div>

            {{-- Average Return --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-2 flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">Average Return</flux:text>
                    <svg class="size-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
                <flux:heading size="2xl" class="font-bold {{ $averageReturn > 0 ? 'text-green-600 dark:text-green-400' : ($averageReturn < 0 ? 'text-red-600 dark:text-red-400' : '') }}">
                    {{ $averageReturn > 0 ? '+' : '' }}{{ number_format($averageReturn, 2) }}%
                </flux:heading>
            </div>

            {{-- Best Performing Strategy --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-2 flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">Best Strategy</flux:text>
                    <svg class="size-5 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                    </svg>
                </div>
                @if($bestStrategy)
                    <flux:heading size="lg" class="mb-1 font-bold">{{ $bestStrategy->configuration?->name ?? 'N/A' }}</flux:heading>
                    <flux:text class="text-sm font-semibold text-green-600 dark:text-green-400">
                        +{{ number_format($bestStrategy->total_return, 2) }}%
                    </flux:text>
                @else
                    <flux:text class="text-zinc-500">No data yet</flux:text>
                @endif
            </div>

            {{-- Total Trades --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="mb-2 flex items-center justify-between">
                    <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">Total Trades</flux:text>
                    <svg class="size-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                    </svg>
                </div>
                <flux:heading size="2xl" class="font-bold">{{ number_format($totalTrades) }}</flux:heading>
            </div>
        </div>

        {{-- Recent Experiments Section --}}
        <div class="mb-8">
            <div class="mb-6 flex items-center justify-between">
                <flux:heading size="lg">Recent Experiments</flux:heading>
                <flux:button wire:navigate href="{{ route('experiments.index') }}" variant="ghost" size="sm">
                    View All
                </flux:button>
            </div>

            @if($recentExperiments->count() > 0)
                <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-800">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Configuration</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Return</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Win Rate</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                            @foreach($recentExperiments as $experiment)
                                <tr class="cursor-pointer transition hover:bg-zinc-50 dark:hover:bg-zinc-800" wire:click="$navigate('{{ route('results.show', $experiment) }}')" wire:navigate>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <flux:text class="font-medium">{{ $experiment->configuration?->name ?? 'Unknown' }}</flux:text>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <flux:text class="text-zinc-600 dark:text-zinc-400">
                                            {{ $experiment->created_at->format('M d, Y') }}
                                        </flux:text>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        @if($experiment->isCompleted() && $experiment->total_return !== null)
                                            <flux:text class="font-semibold {{ $experiment->total_return > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                                {{ $experiment->total_return > 0 ? '+' : '' }}{{ number_format($experiment->total_return, 2) }}%
                                            </flux:text>
                                        @else
                                            <flux:text class="text-zinc-400">-</flux:text>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        @if($experiment->isCompleted() && $experiment->win_rate !== null)
                                            <flux:text class="text-zinc-600 dark:text-zinc-400">
                                                {{ number_format($experiment->win_rate, 1) }}%
                                            </flux:text>
                                        @else
                                            <flux:text class="text-zinc-400">-</flux:text>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        @if($experiment->status === 'completed')
                                            <flux:badge color="green" size="sm">Completed</flux:badge>
                                        @elseif($experiment->status === 'running')
                                            <flux:badge color="yellow" size="sm">Running</flux:badge>
                                        @elseif($experiment->status === 'failed')
                                            <flux:badge color="red" size="sm">Failed</flux:badge>
                                        @else
                                            <flux:badge color="zinc" size="sm">{{ ucfirst($experiment->status) }}</flux:badge>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="rounded-lg border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                    <svg class="mx-auto mb-4 size-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    <flux:heading size="lg" class="mb-2">No Experiments Yet</flux:heading>
                    <flux:text class="mb-4 text-zinc-600 dark:text-zinc-400">Get started by running your first backtest experiment</flux:text>
                    <flux:button wire:navigate href="{{ route('experiments.create') }}">Run First Experiment</flux:button>
                </div>
            @endif
        </div>

        {{-- Quick Actions Section --}}
        <div class="mb-8">
            <flux:heading size="lg" class="mb-6">Quick Actions</flux:heading>
            <div class="grid gap-4 md:grid-cols-3">
                <flux:button wire:navigate href="{{ route('experiments.create') }}" variant="primary" size="base" class="w-full justify-center">
                    <svg class="mr-2 size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Run New Experiment
                </flux:button>

                <flux:button wire:navigate href="{{ route('configurations.create') }}" variant="ghost" size="base" class="w-full justify-center">
                    <svg class="mr-2 size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Create Configuration
                </flux:button>

                <flux:button wire:navigate href="{{ route('stocks.index') }}" variant="ghost" size="base" class="w-full justify-center">
                    <svg class="mr-2 size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                    </svg>
                    Fetch Latest Data
                </flux:button>
            </div>
        </div>
    </div>
</div>
