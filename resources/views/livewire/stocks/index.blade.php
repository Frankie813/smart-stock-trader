<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <flux:heading size="xl" class="mb-2">Stocks</flux:heading>
                <flux:text>Manage stocks tracked for trading predictions</flux:text>
            </div>
            <flux:button wire:navigate href="{{ route('stocks.create') }}" variant="primary">
                <svg class="mr-2 size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Add Stock
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

        {{-- Search Bar --}}
        <div class="mb-6">
            <flux:input
                wire:model.live.debounce.300ms="search"
                type="text"
                placeholder="Search stocks by symbol or name..."
                icon="magnifying-glass"
            />
        </div>

        {{-- Stocks Table --}}
        @if($stocks->count() > 0)
            <div class="mb-6 overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                    <thead class="bg-zinc-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Symbol</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Price Data</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Predictions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                        @foreach($stocks as $stock)
                            <tr>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:text class="font-semibold">{{ $stock->symbol }}</flux:text>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:text class="text-zinc-600 dark:text-zinc-400">{{ $stock->name }}</flux:text>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        @if($stock->is_active)
                                            <flux:badge color="green" size="sm">Active</flux:badge>
                                        @else
                                            <flux:badge color="zinc" size="sm">Inactive</flux:badge>
                                        @endif
                                        <button
                                            wire:click="toggleStatus({{ $stock->id }})"
                                            class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                        >
                                            Toggle
                                        </button>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:text class="text-zinc-600 dark:text-zinc-400">
                                        {{ number_format($stock->prices_count) }} records
                                    </flux:text>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <flux:text class="text-zinc-600 dark:text-zinc-400">
                                        {{ number_format($stock->predictions_count) }}
                                    </flux:text>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    <div class="flex gap-2">
                                        <flux:button variant="ghost" size="sm">
                                            Fetch Data
                                        </flux:button>
                                        @if($stock->predictions_count === 0 && $stock->prices_count === 0)
                                            <flux:button wire:click="delete({{ $stock->id }})" wire:confirm="Are you sure?" variant="ghost" size="sm" class="text-red-600">
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
                {{ $stocks->links() }}
            </div>
        @else
            <div class="rounded-lg border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <svg class="mx-auto mb-4 size-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                </svg>
                <flux:heading size="lg" class="mb-2">No Stocks Found</flux:heading>
                <flux:text class="mb-4 text-zinc-600 dark:text-zinc-400">
                    @if($search)
                        No stocks match your search criteria.
                    @else
                        Add your first stock to start tracking predictions.
                    @endif
                </flux:text>
                @if(!$search)
                    <flux:button wire:navigate href="{{ route('stocks.create') }}">Add Stock</flux:button>
                @endif
            </div>
        @endif
    </div>
</div>
