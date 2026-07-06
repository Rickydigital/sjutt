<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alumni_educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumni_id')->constrained('alumni')->cascadeOnDelete();
            $table->foreignId('faculty_id')->nullable()->constrained('faculties')->nullOnDelete();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete();
            $table->foreignId('graduation_year_id')->nullable()->constrained('graduation_years')->nullOnDelete();
            $table->string('degree_program_major')->nullable();
            $table->timestamps();
        });

        Schema::create('alumni_employments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumni_id')->constrained('alumni')->cascadeOnDelete();
            $table->foreignId('employment_state_id')->nullable()->constrained('employment_states')->nullOnDelete();
            $table->foreignId('employment_sector_id')->nullable()->constrained('employment_sectors')->nullOnDelete();
            $table->foreignId('employment_year_id')->nullable()->constrained('employment_years')->nullOnDelete();
            $table->string('organization')->nullable();
            $table->boolean('is_current')->default(true);
            $table->timestamps();
        });

        Schema::create('alumni_social_platform', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumni_id')->constrained('alumni')->cascadeOnDelete();
            $table->foreignId('social_platform_id')->constrained('social_platforms')->cascadeOnDelete();
            $table->boolean('accepted_invitation')->default(false);
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['alumni_id', 'social_platform_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni_social_platform');
        Schema::dropIfExists('alumni_employments');
        Schema::dropIfExists('alumni_educations');
    }
};
