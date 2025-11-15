<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAssessedWeightsToTransactionsTable extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Menyimpan rincian { "Kertas": 500, "Logam Besi": 200 }
            $table->json('assessed_weights')->nullable()->after('waste_types');
            // Kolom ini akan kita gunakan untuk foto bukti dari Bank Sampah
            $table->string('photo_url_bank_sampah', 2048)->nullable()->after('photo_url');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['assessed_weights', 'photo_url_bank_sampah']);
        });
    }
}