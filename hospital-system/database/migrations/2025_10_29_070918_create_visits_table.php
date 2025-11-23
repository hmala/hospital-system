<?php
// database/migrations/2024_01_09_create_visits_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->onDelete('cascade');
            $table->foreignId('doctor_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('department_id')->constrained()->onDelete('cascade');
            
            $table->date('visit_date');
            $table->enum('visit_type', ['checkup', 'followup', 'emergency', 'surgery', 'lab', 'radiology']);
            $table->text('chief_complaint'); // الشكوى الرئيسية
            $table->text('diagnosis')->nullable(); // التشخيص
            $table->text('treatment')->nullable(); // العلاج الموصوف
            $table->text('prescription')->nullable(); // الوصفة الطبية
            $table->text('notes')->nullable(); // ملاحظات الطبيب
            
            // العلامات الحيوية
            $table->json('vital_signs')->nullable(); // {bp: '120/80', temp: '37', weight: '70', height: '170'}
            
            $table->date('next_visit_date')->nullable(); // موعد المتابعة
            $table->boolean('is_completed')->default(false);
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('visits');
    }
};