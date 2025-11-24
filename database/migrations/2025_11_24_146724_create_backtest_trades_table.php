<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backtest_trades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('backtest_result_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
            $table->date('entry_date');
            $table->date('exit_date');
            $table->decimal('entry_price', 12, 4);
            $table->decimal('exit_price', 12, 4);
            $table->integer('shares');
            $table->string('prediction'); // 'up' or 'down'
            $table->string('actual_direction'); // 'up' or 'down'
            $table->boolean('was_correct');
            $table->decimal('profit_loss', 12, 2);
            $table->decimal('return_percentage', 8, 4);
            $table->decimal('confidence_score', 5, 4)->nullable();
            $table->decimal('commission', 8, 2)->default(0);
            $table->string('exit_reason')->nullable(); // 'eod', 'stop_loss', 'take_profit'
            $table->timestamps();
            
            $table->index('backtest_result_id');
            $table->index('stock_id');
            $table->index(['entry_date', 'exit_date']);
            $table->index('was_correct');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backtest_trades');
    }
};
