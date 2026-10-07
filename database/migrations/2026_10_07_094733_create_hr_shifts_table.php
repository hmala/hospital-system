<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('اسم الشفت (صباحي، خفارة)');
            $table->time('start_time')->comment('وقت بداية الشفت');
            $table->time('end_time')->comment('وقت نهاية الشفت');
            $table->string('color_code', 20)->default('#0d6efd')->comment('لون التمييز في الجدول');
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_shifts');
    }
};
