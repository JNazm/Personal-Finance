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
        Schema::table('commitments', function (Blueprint $table) {
            $table->string('type')->default('infinite')->after('date_started'); // 'infinite' or 'normal'
            $table->date('end_date')->nullable()->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('commitments', function (Blueprint $table) {
            $table->dropColumn(['type', 'end_date']);
        });
    }
};
