<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->string('player_id')->nullable()->after('plat_nomor');
        });
    }
    public function down()
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('player_id');
        });
    }
};
