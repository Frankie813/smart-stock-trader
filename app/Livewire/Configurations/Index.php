<?php

namespace App\Livewire\Configurations;

use App\Models\ModelConfiguration;
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

    public function setAsDefault(int $configurationId): void
    {
        $configuration = ModelConfiguration::findOrFail($configurationId);
        $configuration->setAsDefault();

        session()->flash('success', 'Configuration set as default successfully!');
    }

    public function delete(int $configurationId): void
    {
        $configuration = ModelConfiguration::findOrFail($configurationId);

        if ($configuration->is_default) {
            session()->flash('error', 'Cannot delete the default configuration.');

            return;
        }

        if ($configuration->experiments()->count() > 0) {
            session()->flash('error', 'Cannot delete configuration with existing experiments.');

            return;
        }

        $configuration->delete();

        session()->flash('success', 'Configuration deleted successfully!');
    }

    public function render(): View
    {
        $configurations = ModelConfiguration::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', "%{$this->search}%")
                    ->orWhere('description', 'like', "%{$this->search}%");
            })
            ->withCount('experiments')
            ->latest()
            ->paginate(12);

        return view('livewire.configurations.index', [
            'configurations' => $configurations,
        ]);
    }
}
