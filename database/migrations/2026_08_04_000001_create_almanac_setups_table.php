<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('almanac_setups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->restrictOnDelete();
            $table->string('title');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->timestamp('activated_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'academic_year_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('almanac_setups');
    }
};
