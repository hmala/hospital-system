<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('radiology_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('radiology_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('radiologist_id')->constrained('users')->onDelete('cascade'); // الطبيب المقرأ
            $table->text('findings'); // النتائج والملاحظات
            $table->text('impression'); // التقرير الطبي
            $table->text('recommendations')->nullable(); // التوصيات
            $table->json('images')->nullable(); // مسارات الصور (JSON array)
            $table->boolean('is_preliminary')->default(true); // هل التقرير أولي؟
            $table->dateTime('reported_at'); // تاريخ التقرير
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('radiology_results');
    }
};
