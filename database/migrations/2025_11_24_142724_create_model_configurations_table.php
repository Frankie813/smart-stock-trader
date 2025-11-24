<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('model_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('hyperparameters'); // XGBoost settings
            $table->json('features_enabled'); // Which technical indicators to use
            $table->json('trading_rules'); // Stop loss, take profit, etc.
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            
            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('model_configurations');
    }
};
