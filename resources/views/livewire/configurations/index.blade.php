<div>
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8 flex items-center justify-between">
            <div>
                <flux:heading size="xl" class="mb-2">Model Configurations</flux:heading>
                <flux:text>Manage your machine learning model settings and trading strategies</flux:text>
            </div>
            <flux:button wire:navigate href="{{ route('configurations.create') }}" variant="primary">
                <svg class="mr-2 size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create Configuration
            </flux:button>
        </div>

        {{-- Success/Error Messages --}}
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
                placeholder="Search configurations..."
                icon="magnifying-glass"
            />
        </div>

        {{-- Configurations Grid --}}
        @if($configurations->count() > 0)
            <div class="mb-8 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach($configurations as $config)
                    <div class="rounded-lg border border-zinc-200 bg-white p-6 transition hover:shadow-lg dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="mb-4 flex items-start justify-between">
                            <div class="flex-1">
                                <div class="mb-1 flex items-center gap-2">
                                    <flux:heading size="lg">{{ $config->name }}</flux:heading>
                                    @if($config->is_default)
                                        <svg class="size-5 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                                        </svg>
                                    @endif
                                </div>
                                <flux:text class="mb-3 text-sm text-zinc-600 dark:text-zinc-400">
                                    {{ Str::limit($config->description, 60) }}
                                </flux:text>
                            </div>
                        </div>

                        <div class="mb-4 space-y-2">
                            <div class="flex items-center justify-between">
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">Features</flux:text>
                                <flux:badge color="blue" size="sm">{{ $config->enabled_features_count }}</flux:badge>
                            </div>
                            <div class="flex items-center justify-between">
                                <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">Experiments</flux:text>
                                <flux:badge color="zinc" size="sm">{{ $config->experiments_count }}</flux:badge>
                            </div>
                        </div>

                        <div class="flex gap-2">
                            <flux:button wire:navigate href="{{ route('configurations.edit', $config) }}" variant="ghost" size="sm" class="flex-1">
                                Edit
                            </flux:button>
                            <flux:button wire:navigate href="{{ route('experiments.create', ['config' => $config->id]) }}" variant="primary" size="sm" class="flex-1">
                                Use
                            </flux:button>
                        </div>

                        <div class="mt-3 flex gap-2">
                            @if(!$config->is_default)
                                <flux:button wire:click="setAsDefault({{ $config->id }})" variant="ghost" size="sm" class="flex-1">
                                    <svg class="mr-1 size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                                    </svg>
                                    Set Default
                                </flux:button>
                            @endif
                            @if(!$config->is_default && $config->experiments_count === 0)
                                <flux:button wire:click="delete({{ $config->id }})" wire:confirm="Are you sure you want to delete this configuration?" variant="ghost" size="sm" class="text-red-600">
                                    <svg class="mr-1 size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                    Delete
                                </flux:button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $configurations->links() }}
            </div>
        @else
            <div class="rounded-lg border border-zinc-200 bg-white p-12 text-center dark:border-zinc-700 dark:bg-zinc-900">
                <svg class="mx-auto mb-4 size-12 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <flux:heading size="lg" class="mb-2">No Configurations Found</flux:heading>
                <flux:text class="mb-4 text-zinc-600 dark:text-zinc-400">
                    @if($search)
                        No configurations match your search criteria.
                    @else
                        Get started by creating your first model configuration.
                    @endif
                </flux:text>
                @if(!$search)
                    <flux:button wire:navigate href="{{ route('configurations.create') }}">Create Configuration</flux:button>
                @endif
            </div>
        @endif
    </div>
</div>
