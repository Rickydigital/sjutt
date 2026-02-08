<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('elections', function (Blueprint $table) {
            $table->id();
            $table->string('title');

            $table->date('start_date');
            $table->date('end_date');

            $table->dateTime('open_at');
            $table->dateTime('close_at');

            $table->boolean('is_active')->default(true);

            $table->enum('status', ['draft', 'open', 'closed', 'published'])
                  ->default('draft');

            $table->timestamps();

            // Safety
            $table->index(['open_at', 'close_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('elections');
    }
};
