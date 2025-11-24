<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backtest_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('experiment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_id')->nullable()->constrained()->cascadeOnDelete(); // null = overall results
            $table->foreignId('model_configuration_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('initial_capital', 15, 2);
            $table->decimal('final_capital', 15, 2);
            $table->decimal('total_return', 10, 2); // Percentage
            $table->integer('total_trades')->default(0);
            $table->integer('winning_trades')->default(0);
            $table->integer('losing_trades')->default(0);
            $table->decimal('win_rate', 5, 2)->nullable(); // Percentage
            $table->decimal('total_profit_loss', 15, 2);
            $table->decimal('accuracy_percentage', 5, 2)->nullable();
            $table->decimal('sharpe_ratio', 8, 4)->nullable();
            $table->decimal('max_drawdown', 10, 2)->nullable();
            $table->decimal('avg_profit_per_trade', 12, 2)->nullable();
            $table->decimal('avg_loss_per_trade', 12, 2)->nullable();
            $table->decimal('profit_factor', 8, 4)->nullable();
            $table->decimal('largest_win', 12, 2)->nullable();
            $table->decimal('largest_loss', 12, 2)->nullable();
            $table->string('model_version')->nullable();
            $table->timestamps();
            
            $table->index('experiment_id');
            $table->index('stock_id');
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backtest_results');
    }
};
