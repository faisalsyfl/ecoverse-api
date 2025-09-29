<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id('transaction_id');
            
            // $table->foreignId('warga_id')->constrained('users')->onDelete('restrict');
            // $table->foreignId('kurir_id')->nullable()->constrained('users')->onDelete('set null');
            // $table->foreignId('bank_sampah_id')->constrained('users')->onDelete('restrict');
            
            $table->enum('method', ['pickup', 'dropoff']);
            $table->enum('status', ['mencari_kurir', 'dijemput', 'diantar', 'selesai', 'dibatalkan']);
            
            $table->timestamps(); // Membuat created_at dan updated_at
        });
    }

    public function down()
    {
        Schema::dropIfExists('transactions');
    }
}