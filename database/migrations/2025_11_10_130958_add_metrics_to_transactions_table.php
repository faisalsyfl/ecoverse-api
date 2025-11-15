<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMetricsToTransactionsTable extends Migration
{
    public function up()
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Menambahkan kolom setelah kolom 'status'
            $table->decimal('weight_kg', 8, 2)->default(0.00)->after('status');
            $table->decimal('value', 10, 2)->default(0.00)->after('weight_kg');
        });
    }

    public function down()
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['weight_kg', 'value']);
        });
    }
}