<?php

namespace App\Livewire\Results;

use App\Models\BacktestTrade;
use App\Models\Experiment;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use WithPagination;

    public Experiment $experiment;

    public function mount(Experiment $experiment): void
    {
        $this->experiment = $experiment;
    }

    public function render(): View
    {
        $overallResult = $this->experiment->backtestResults()->overall()->first();
        $stockResults = $this->experiment->backtestResults()->forStock()->with('stock')->get();

        $recentTrades = BacktestTrade::query()
            ->whereIn('backtest_result_id', $this->experiment->backtestResults()->pluck('id'))
            ->with('stock')
            ->latest('trade_date')
            ->paginate(20);

        return view('livewire.results.show', [
            'overallResult' => $overallResult,
            'stockResults' => $stockResults,
            'recentTrades' => $recentTrades,
        ]);
    }
}
