<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeliveryFeeToTransactionsTable extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Biaya ongkos kirim (jika ada)
            $table->decimal('delivery_fee', 10, 2)->default(0.00)->after('value');
        });
    }
    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('delivery_fee');
        });
    }
}