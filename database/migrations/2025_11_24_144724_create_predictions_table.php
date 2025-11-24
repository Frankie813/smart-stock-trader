<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('predictions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_id')->constrained()->cascadeOnDelete();
            $table->foreignId('experiment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->date('prediction_date');
            $table->string('predicted_direction'); // 'up' or 'down'
            $table->decimal('confidence_score', 5, 4); // 0.0000 to 1.0000
            $table->string('actual_direction')->nullable(); // 'up' or 'down' (filled after market close)
            $table->boolean('was_correct')->nullable();
            $table->string('model_version')->nullable();
            $table->json('features_used')->nullable();
            $table->timestamps();
            
            $table->index(['stock_id', 'prediction_date']);
            $table->index('prediction_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('predictions');
    }
};
