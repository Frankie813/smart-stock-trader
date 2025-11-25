<?php

namespace App\Livewire\Stocks;

use App\Models\Stock;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Component;

class Create extends Component
{
    public string $symbol = '';

    public string $name = '';

    public string $exchange = 'NASDAQ';

    public bool $fetchDataAfterAdding = true;

    public function save(): void
    {
        $this->validate([
            'symbol' => [
                'required',
                'string',
                'min:1',
                'max:5',
                'regex:/^[A-Z]+$/',
                Rule::unique('stocks', 'symbol'),
            ],
            'name' => 'required|string|max:255',
            'exchange' => 'required|in:NASDAQ,NYSE,AMEX',
        ]);

        $stock = Stock::create([
            'symbol' => strtoupper($this->symbol),
            'name' => $this->name,
            'exchange' => $this->exchange,
            'is_active' => true,
        ]);

        session()->flash('success', 'Stock added successfully!');

        $this->redirect(route('stocks.index'), navigate: true);
    }

    public function render(): View
    {
        return view('livewire.stocks.create');
    }
}
