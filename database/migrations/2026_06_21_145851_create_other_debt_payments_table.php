<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('other_debt_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('other_debt_id')->constrained()->onDelete('cascade');
            $table->unsignedInteger('month_index');
            $table->boolean('is_paid')->default(false);
            $table->timestamps();
            $table->unique(['other_debt_id', 'month_index']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('other_debt_payments');
    }
};
