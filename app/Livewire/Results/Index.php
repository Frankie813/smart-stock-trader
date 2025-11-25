<?php

namespace App\Livewire\Results;

use App\Models\Experiment;
use Illuminate\View\View;
use Livewire\Component;

class Index extends Component
{
    public array $selectedExperiments = [];

    public function toggleExperiment(int $experimentId): void
    {
        if (in_array($experimentId, $this->selectedExperiments)) {
            $this->selectedExperiments = array_diff($this->selectedExperiments, [$experimentId]);
        } else {
            if (count($this->selectedExperiments) >= 4) {
                session()->flash('error', 'You can compare up to 4 experiments at a time.');

                return;
            }
            $this->selectedExperiments[] = $experimentId;
        }
    }

    public function clearSelection(): void
    {
        $this->selectedExperiments = [];
    }

    public function render(): View
    {
        $completedExperiments = Experiment::completed()
            ->with('configuration')
            ->latest('completed_at')
            ->limit(20)
            ->get();

        $selectedExperimentsData = [];
        if (count($this->selectedExperiments) > 0) {
            $selectedExperimentsData = Experiment::query()
                ->whereIn('id', $this->selectedExperiments)
                ->with(['configuration', 'backtestResults' => function ($query) {
                    $query->overall();
                }])
                ->get();
        }

        return view('livewire.results.index', [
            'completedExperiments' => $completedExperiments,
            'selectedExperimentsData' => $selectedExperimentsData,
        ]);
    }
}
