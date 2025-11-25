<div>
    <div class="mx-auto max-w-3xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="mb-8">
            <flux:heading size="xl" class="mb-2">Add Stock</flux:heading>
            <flux:text>Add a new stock to track for trading predictions</flux:text>
        </div>

        <form wire:submit="save">
            <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-900">
                <div class="space-y-6">
                    <flux:field>
                        <flux:label>Stock Symbol</flux:label>
                        <flux:input
                            wire:model="symbol"
                            type="text"
                            placeholder="AAPL"
                            maxlength="5"
                            class="uppercase"
                        />
                        <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">
                            1-5 letter ticker symbol (e.g., AAPL, MSFT, TSLA)
                        </flux:text>
                        <flux:error name="symbol" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Company Name</flux:label>
                        <flux:input
                            wire:model="name"
                            type="text"
                            placeholder="Apple Inc."
                        />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Exchange</flux:label>
                        <flux:select wire:model="exchange">
                            <option value="NASDAQ">NASDAQ</option>
                            <option value="NYSE">NYSE</option>
                            <option value="AMEX">AMEX</option>
                        </flux:select>
                        <flux:error name="exchange" />
                    </flux:field>

                    <flux:field>
                        <flux:checkbox wire:model="fetchDataAfterAdding">
                            Fetch historical data after adding
                        </flux:checkbox>
                    </flux:field>
                </div>

                <div class="mt-6 flex items-center justify-between">
                    <flux:button type="button" wire:navigate href="{{ route('stocks.index') }}" variant="ghost">
                        Cancel
                    </flux:button>
                    <flux:button type="submit" variant="primary">
                        Add Stock
                    </flux:button>
                </div>
            </div>
        </form>
    </div>
</div>
