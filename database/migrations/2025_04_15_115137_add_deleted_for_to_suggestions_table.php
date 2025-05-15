<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeletedForToSuggestionsTable extends Migration
{
    public function up()
    {
        Schema::table('suggestions', function (Blueprint $table) {
            $table->json('deleted_for')->nullable()->after('status'); // Stores user IDs who deleted for themselves
        });
    }

    public function down()
    {
        Schema::table('suggestions', function (Blueprint $table) {
            $table->dropColumn('deleted_for');
        });
    }
}