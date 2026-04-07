<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('location_product_thresholds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->integer('alert_quantity')->default(0);
            $table->integer('reorder_level')->default(0);
            $table->timestamps();
            $table->unique(['location_id', 'product_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('location_product_thresholds');
    }
};
