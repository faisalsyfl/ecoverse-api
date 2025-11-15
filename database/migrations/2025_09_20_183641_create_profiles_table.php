<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfilesTable extends Migration
{
    public function up()
    {
        Schema::create('profiles', function (Blueprint $table) {
            $table->id('profile_id');
            $table->foreignId('user_id')->unique()->constrained('users', 'user_id')->onDelete('cascade');
            $table->decimal('rewards_balance', 10, 2)->default(0.00);
            $table->boolean('is_accepting_orders')->default(false);
            $table->string('group_name')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('village', 100)->nullable();
            $table->string('rw', 5)->nullable();
            $table->string('rt', 5)->nullable();
            $table->string('avatar_url', 2048)->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('profiles');
    }
}