<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWasteTypesTable extends Migration
{
    public function up()
    {
        Schema::create('waste_types', function (Blueprint $table) {
            $table->id();
            // Foreign key ke bank sampah yang memiliki jenis ini
            $table->foreignId('bank_sampah_id')->constrained('users', 'user_id')->onDelete('cascade');

            $table->string('name');
            $table->decimal('price_per_gram', 10, 2); // Harga per gram

            $table->timestamps();
        });
    }
    public function down() { Schema::dropIfExists('waste_types'); }
}