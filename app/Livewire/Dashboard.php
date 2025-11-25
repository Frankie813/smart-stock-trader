<?php

namespace App\Livewire;

use App\Models\BacktestResult;
use App\Models\Experiment;
use Illuminate\View\View;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(): View
    {
        $totalExperiments = Experiment::count();
        $completedExperiments = Experiment::completed()->get();

        $averageReturn = $completedExperiments->avg('total_return') ?? 0;

        $bestStrategy = $completedExperiments->sortByDesc('total_return')->first();

        $totalTrades = BacktestResult::sum('total_trades');

        $recentExperiments = Experiment::with('configuration')
            ->latest('created_at')
            ->limit(5)
            ->get();

        return view('livewire.dashboard', [
            'totalExperiments' => $totalExperiments,
            'averageReturn' => $averageReturn,
            'bestStrategy' => $bestStrategy,
            'totalTrades' => $totalTrades,
            'recentExperiments' => $recentExperiments,
        ]);
    }
}
