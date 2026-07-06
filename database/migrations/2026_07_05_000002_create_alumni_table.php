<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('alumni', function (Blueprint $table) {
            $table->id();
            $table->string('f_name');
            $table->string('m_name')->nullable();
            $table->string('l_name');
            $table->string('email')->unique();
            $table->string('password');
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('profile_photo')->nullable();
            $table->string('nida_number', 30)->nullable()->unique();
            $table->foreignId('settlement_country_id')->nullable()->constrained('countries')->nullOnDelete();
            $table->string('settlement_region')->nullable();
            $table->string('settlement_city')->nullable();
            $table->boolean('interested_meetings')->default(false);
            $table->boolean('interested_social_platform')->default(false);
            $table->enum('status', ['pending', 'active', 'suspended'])->default('pending');
            $table->boolean('is_active')->default(false);
            $table->timestamp('password_changed_at')->nullable();
            $table->timestamp('first_login_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alumni');
    }
};
