<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPlatNomorToProfilesTable extends Migration
{
    public function up()
    {
        Schema::table('profiles', function (Blueprint $table) {
            // Kolom untuk plat nomor kurir
            $table->string('plat_nomor', 20)->nullable()->after('avatar_url');
        });
    }
    public function down()
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn('plat_nomor');
        });
    }
}