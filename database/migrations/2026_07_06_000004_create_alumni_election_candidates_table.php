<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alumni_election_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumni_election_id')->constrained('alumni_elections')->cascadeOnDelete();
            $table->foreignId('alumni_election_position_id')->constrained('alumni_election_positions')->cascadeOnDelete();
            $table->foreignId('alumni_id')->constrained('alumni')->cascadeOnDelete();
            $table->string('photo')->nullable();
            $table->string('surname')->nullable();
            $table->string('first_name')->nullable();
            $table->string('middle_name')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('sex')->nullable();
            $table->string('marital_status')->nullable();
            $table->string('education_level')->nullable();
            $table->string('current_position')->nullable();
            $table->string('institution')->nullable();
            $table->string('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->enum('applicant_type', ['staff_academic','staff_non_academic','alumni'])->default('alumni');
            $table->string('institution_attended')->nullable();
            $table->string('programme_studied')->nullable();
            $table->string('year_graduated')->nullable();
            $table->enum('application_status', ['pending','approved','rejected'])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['alumni_election_id','alumni_election_position_id','alumni_id'], 'alumni_candidate_once_per_position');
        });
    }
    public function down(): void { Schema::dropIfExists('alumni_election_candidates'); }
};
