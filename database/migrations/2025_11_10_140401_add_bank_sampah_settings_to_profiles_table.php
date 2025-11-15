<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBankSampahSettingsToProfilesTable extends Migration
{
    public function up()
    {
        Schema::table('profiles', function (Blueprint $table) {
            // Kolom untuk toggle "Penerimaan Sampah"
            $table->boolean('accepting_waste')->default(true)->after('avatar_url');
            // Kolom untuk toggle "Status Pencairan Rewards"
            $table->boolean('processing_withdrawals')->default(true)->after('accepting_waste');
        });
    }

    public function down()
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropColumn(['accepting_waste', 'processing_withdrawals']);
        });
    }
}