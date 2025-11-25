<?php

use App\Livewire\Configurations\Form as ConfigurationsForm;
use App\Livewire\Configurations\Index as ConfigurationsIndex;
use App\Livewire\Dashboard;
use App\Livewire\Experiments\Create as ExperimentsCreate;
use App\Livewire\Experiments\Index as ExperimentsIndex;
use App\Livewire\Results\Index as ResultsIndex;
use App\Livewire\Results\Show as ResultsShow;
use App\Livewire\Settings\Appearance;
use App\Livewire\Settings\Password;
use App\Livewire\Settings\Profile;
use App\Livewire\Settings\TwoFactor;
use App\Livewire\Stocks\Create as StocksCreate;
use App\Livewire\Stocks\Index as StocksIndex;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', Dashboard::class)->name('dashboard');

    Route::prefix('configurations')->name('configurations.')->group(function () {
        Route::get('/', ConfigurationsIndex::class)->name('index');
        Route::get('/create', ConfigurationsForm::class)->name('create');
        Route::get('/{configuration}/edit', ConfigurationsForm::class)->name('edit');
    });

    Route::prefix('experiments')->name('experiments.')->group(function () {
        Route::get('/', ExperimentsIndex::class)->name('index');
        Route::get('/create', ExperimentsCreate::class)->name('create');
    });

    Route::prefix('results')->name('results.')->group(function () {
        Route::get('/', ResultsIndex::class)->name('index');
        Route::get('/{experiment}', ResultsShow::class)->name('show');
    });

    Route::prefix('stocks')->name('stocks.')->group(function () {
        Route::get('/', StocksIndex::class)->name('index');
        Route::get('/create', StocksCreate::class)->name('create');
    });
});

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Route::get('settings/profile', Profile::class)->name('profile.edit');
    Route::get('settings/password', Password::class)->name('user-password.edit');
    Route::get('settings/appearance', Appearance::class)->name('appearance.edit');

    Route::get('settings/two-factor', TwoFactor::class)
        ->middleware(
            when(
                Features::canManageTwoFactorAuthentication()
                    && Features::optionEnabled(Features::twoFactorAuthentication(), 'confirmPassword'),
                ['password.confirm'],
                [],
            ),
        )
        ->name('two-factor.show');
});
