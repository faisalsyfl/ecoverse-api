<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBankSampahLinkToProfilesTable extends Migration
{
    public function up()
    {
        Schema::table('profiles', function (Blueprint $table) {
            // Kolom ini akan diisi oleh Warga/Kurir saat mendaftar
            $table->foreignId('bank_sampah_id')
                  ->nullable()
                  ->constrained('users', 'user_id') // Mereferensi user_id di tabel users
                  ->onDelete('set null');
        });
    }
    public function down()
    {
        Schema::table('profiles', function (Blueprint $table) {
            $table->dropForeign(['bank_sampah_id']);
            $table->dropColumn('bank_sampah_id');
        });
    }
}