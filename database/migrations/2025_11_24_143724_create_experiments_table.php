<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('experiments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('model_configuration_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('initial_capital', 15, 2)->default(10000.00);
            $table->string('status')->default('pending'); // pending, running, completed, failed
            $table->integer('progress')->default(0); // 0-100
            $table->json('results')->nullable(); // Aggregated results
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index('status');
            $table->index(['start_date', 'end_date']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiments');
    }
};
