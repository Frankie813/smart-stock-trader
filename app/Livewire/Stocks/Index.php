<?php

namespace App\Livewire\Stocks;

use App\Models\Stock;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function toggleStatus(int $stockId): void
    {
        $stock = Stock::findOrFail($stockId);
        $stock->update(['is_active' => ! $stock->is_active]);

        session()->flash('success', 'Stock status updated successfully!');
    }

    public function delete(int $stockId): void
    {
        $stock = Stock::findOrFail($stockId);

        if ($stock->predictions()->count() > 0 || $stock->backtestResults()->count() > 0) {
            session()->flash('error', 'Cannot delete stock with existing predictions or backtest results.');

            return;
        }

        $stock->delete();

        session()->flash('success', 'Stock deleted successfully!');
    }

    public function render(): View
    {
        $stocks = Stock::query()
            ->when($this->search, function ($query) {
                $query->where('symbol', 'like', "%{$this->search}%")
                    ->orWhere('name', 'like', "%{$this->search}%");
            })
            ->withCount(['prices', 'predictions'])
            ->latest()
            ->paginate(10);

        return view('livewire.stocks.index', [
            'stocks' => $stocks,
        ]);
    }
}
