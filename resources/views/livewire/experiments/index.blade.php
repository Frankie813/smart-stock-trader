<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <flux:heading size="xl" class="mb-2">Experiments</flux:heading>
                <flux:text>View and manage your backtesting experiments</flux:text>
            </div>
            <flux:button wire:navigate href="{{ route('experiments.create') }}" variant="primary">
                <svg class="mr-2 size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Run New Experiment
            </flux:button>
        </div>

        @if(session('success'))
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                {{ session('error') }}
            </div>
        @endif

        {{-- Filters --}}
        <div class="mb-6 grid gap-4 sm:grid-cols-3">
            <flux:select wire:model.live="statusFilter">
                <option value="all">All Status</option>
                <option value="pending">Pending</option>
                <option value="running">Running</option>
                <option value="completed">Completed</option>
                <option value="failed">Failed</option>
            </flux:select>

            <flux:select wire:model.live="configFilter">
                <option value="">All Configurations</option>
                @foreach($configurations as $config)
                    <option value="{{ $config->id }}">{{ $config->name }}</option>
                @endforeach
            </flux:select>

            <flux:select wire:model.live="sortBy">
                <option value="newest">Newest First</option>
                <option value="oldest">Oldest First</option>
                <option value="best_return">Best Return</option>
                <option value="worst_return">Worst Return</option>
            </flux:select>
        </div>

        {{-- Experiments Table --}}
        @if($experiments->count() > 0)
            <div class="mb-6 overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Name/ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Configuration</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Date Range</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Return</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Win Rate</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                        @foreach($experiments as $experiment)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:text class="font-medium">
                                        {{ $experiment->name ?? 'Experiment #' . $experiment->id }}
                                    </flux:text>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:text class="text-zinc-600 dark:text-zinc-400">
                                        {{ $experiment->configuration?->name ?? 'N/A' }}
                                    </flux:text>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:text class="text-zinc-600 dark:text-zinc-400">
                                        {{ $experiment->start_date?->format('M d, Y') }} - {{ $experiment->end_date?->format('M d, Y') }}
                                    </flux:text>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    @if($experiment->status === 'completed')
                                        <flux:badge color="green" size="sm">Completed</flux:badge>
                                    @elseif($experiment->status === 'running')
                                        <div class="flex items-center gap-2">
                                            <flux:badge color="yellow" size="sm">Running</flux:badge>
                                            <flux:text class="text-xs text-zinc-500">{{ $experiment->progress }}%</flux:text>
                                        </div>
                                    @elseif($experiment->status === 'failed')
                                        <flux:badge color="red" size="sm">Failed</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">{{ ucfirst($experiment->status) }}</flux:badge>
                                    @endif
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
                                    <div class="flex gap-2">
                                        @if($experiment->isCompleted())
                                            <flux:button wire:navigate href="{{ route('results.show', $experiment) }}" variant="ghost" size="sm">
                                                View
                                            </flux:button>
                                        @endif
                                        @if(!$experiment->isRunning())
                                            <flux:button wire:click="delete({{ $experiment->id }})" wire:confirm="Are you sure?" variant="ghost" size="sm" class="text-red-600">
                                                Delete
                                            </flux:button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $experiments->links() }}
            </div>
        @else
            <div class="rounded-lg border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <svg class="mx-auto mb-4 size-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                </svg>
                <flux:heading size="lg" class="mb-2">No Experiments Found</flux:heading>
                <flux:text class="mb-4 text-zinc-600 dark:text-zinc-400">
                    Run your first backtest experiment to get started
                </flux:text>
                <flux:button wire:navigate href="{{ route('experiments.create') }}">Run First Experiment</flux:button>
            </div>
        @endif
    </div>
</div>
