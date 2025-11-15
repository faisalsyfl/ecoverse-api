<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWithdrawalsTable extends Migration
{
    public function up()
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id('withdrawal_id');

            $table->foreignId('warga_id')->constrained('users','user_id')->onDelete('restrict');
            $table->foreignId('bank_sampah_id')->constrained('users','user_id')->onDelete('restrict');

            $table->decimal('amount', 10, 2);
            $table->enum('status', ['diajukan', 'selesai', 'ditolak'])->default('diajukan');

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('processed_at')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('withdrawals');
    }
}