<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeReactionsToPolymorphic extends Migration
{
    public function up()
    {
        Schema::table('reactions', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->morphs('reactable'); 
        });
    }

    public function down()
    {
        Schema::table('reactions', function (Blueprint $table) {
            $table->dropMorphs('reactable'); 
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); 
        });
    }
}