<?php

namespace App\Livewire\Experiments;

use App\Models\Experiment;
use App\Models\ModelConfiguration;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    public string $statusFilter = 'all';

    public ?int $configFilter = null;

    public string $sortBy = 'newest';

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedConfigFilter(): void
    {
        $this->resetPage();
    }

    public function updatedSortBy(): void
    {
        $this->resetPage();
    }

    public function delete(int $experimentId): void
    {
        $experiment = Experiment::findOrFail($experimentId);

        if ($experiment->isRunning()) {
            session()->flash('error', 'Cannot delete a running experiment.');

            return;
        }

        $experiment->delete();

        session()->flash('success', 'Experiment deleted successfully!');
    }

    public function render(): View
    {
        $experiments = Experiment::query()
            ->with('configuration')
            ->when($this->statusFilter !== 'all', function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->configFilter, function ($query) {
                $query->where('model_configuration_id', $this->configFilter);
            })
            ->when($this->sortBy === 'newest', function ($query) {
                $query->latest('created_at');
            })
            ->when($this->sortBy === 'oldest', function ($query) {
                $query->oldest('created_at');
            })
            ->when($this->sortBy === 'best_return', function ($query) {
                $query->completed()->orderByDesc('results->overall->total_return');
            })
            ->when($this->sortBy === 'worst_return', function ($query) {
                $query->completed()->orderBy('results->overall->total_return');
            })
            ->paginate(10);

        $configurations = ModelConfiguration::all();

        return view('livewire.experiments.index', [
            'experiments' => $experiments,
            'configurations' => $configurations,
        ]);
    }
}
