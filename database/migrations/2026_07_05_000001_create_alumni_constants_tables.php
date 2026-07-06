<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('graduation_years', function (Blueprint $table) { $table->id(); $table->year('year')->unique(); $table->timestamps(); });
        Schema::create('employment_years', function (Blueprint $table) { $table->id(); $table->year('year')->unique(); $table->timestamps(); });
        Schema::create('employment_states', function (Blueprint $table) { $table->id(); $table->string('name')->unique(); $table->timestamps(); });
        Schema::create('employment_sectors', function (Blueprint $table) { $table->id(); $table->string('name')->unique(); $table->timestamps(); });
        Schema::create('countries', function (Blueprint $table) { $table->id(); $table->string('name')->unique(); $table->string('code')->nullable()->unique(); $table->timestamps(); });
        Schema::create('social_platforms', function (Blueprint $table) { $table->id(); $table->string('name')->unique(); $table->timestamps(); });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_platforms');
        Schema::dropIfExists('countries');
        Schema::dropIfExists('employment_sectors');
        Schema::dropIfExists('employment_states');
        Schema::dropIfExists('employment_years');
        Schema::dropIfExists('graduation_years');
    }
};
