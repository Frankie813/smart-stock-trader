<?php

namespace App\Livewire\Experiments;

use App\Models\Experiment;
use App\Models\ModelConfiguration;
use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\View\View;
use Livewire\Component;

class Create extends Component
{
    public ?int $configurationId = null;

    public array $selectedStocks = [];

    public string $startDate = '';

    public string $endDate = '';

    public float $initialCapital = 10000;

    public function mount(?int $config = null): void
    {
        if ($config) {
            $this->configurationId = $config;
        }

        $this->startDate = Carbon::now()->subMonths(6)->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
    }

    public function toggleStock(int $stockId): void
    {
        if (in_array($stockId, $this->selectedStocks)) {
            $this->selectedStocks = array_diff($this->selectedStocks, [$stockId]);
        } else {
            $this->selectedStocks[] = $stockId;
        }
    }

    public function selectAllStocks(): void
    {
        $this->selectedStocks = Stock::where('is_active', true)->pluck('id')->toArray();
    }

    public function deselectAllStocks(): void
    {
        $this->selectedStocks = [];
    }

    public function runExperiment(): void
    {
        $this->validate([
            'configurationId' => 'required|exists:model_configurations,id',
            'selectedStocks' => 'required|array|min:1',
            'selectedStocks.*' => 'exists:stocks,id',
            'startDate' => 'required|date',
            'endDate' => 'required|date|after:startDate',
            'initialCapital' => 'required|numeric|min:100|max:1000000',
        ]);

        $experiment = Experiment::create([
            'model_configuration_id' => $this->configurationId,
            'stock_ids' => $this->selectedStocks,
            'name' => 'Experiment '.Carbon::now()->format('Y-m-d H:i'),
            'start_date' => $this->startDate,
            'end_date' => $this->endDate,
            'initial_capital' => $this->initialCapital,
            'status' => 'pending',
            'progress' => 0,
        ]);

        // Dispatch job to run the experiment
        \App\Jobs\RunExperiment::dispatch($experiment);

        session()->flash('success', 'Experiment created successfully! Starting backtest...');

        $this->redirect(route('experiments.index'), navigate: true);
    }

    public function render(): View
    {
        $configurations = ModelConfiguration::all();
        $stocks = Stock::where('is_active', true)->get();

        $selectedConfiguration = $this->configurationId
            ? ModelConfiguration::find($this->configurationId)
            : null;

        $tradingDays = 0;
        if ($this->startDate && $this->endDate) {
            try {
                $start = Carbon::parse($this->startDate);
                $end = Carbon::parse($this->endDate);
                $tradingDays = $start->diffInDays($end);
            } catch (\Exception $e) {
                $tradingDays = 0;
            }
        }

        return view('livewire.experiments.create', [
            'configurations' => $configurations,
            'stocks' => $stocks,
            'selectedConfiguration' => $selectedConfiguration,
            'tradingDays' => $tradingDays,
        ]);
    }
}
