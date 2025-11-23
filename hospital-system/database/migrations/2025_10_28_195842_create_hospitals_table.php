// database/migrations/2024_01_01_create_hospitals_table.php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('hospitals', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('owner_name');
            $table->string('phone');
            $table->text('address');
            $table->string('email')->nullable();
            $table->string('license_number')->unique();
            $table->integer('bed_capacity')->default(0);
            $table->boolean('has_emergency')->default(false);
            $table->boolean('has_pharmacy')->default(false);
            $table->boolean('has_lab')->default(false);
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hospitals');
    }
};