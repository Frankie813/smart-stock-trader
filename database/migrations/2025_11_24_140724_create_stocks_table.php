<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->string('symbol', 10)->unique(); // e.g., "AAPL"
            $table->string('name'); // e.g., "Apple Inc."
            $table->string('exchange')->nullable(); // e.g., "NASDAQ"
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('symbol');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
