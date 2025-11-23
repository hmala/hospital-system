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
        Schema::table('surgeries', function (Blueprint $table) {
            $table->text('diagnosis')->nullable()->after('description'); // التشخيص
            $table->text('pre_op_medications')->nullable()->after('diagnosis'); // الأدوية قبل العملية
            $table->integer('estimated_duration')->nullable()->after('pre_op_medications'); // مدة العملية المتوقعة بالدقائق
            $table->text('required_tests')->nullable()->after('estimated_duration'); // التحاليل المطلوبة
            $table->text('anesthesia_type')->nullable()->after('required_tests'); // نوع التخدير
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surgeries', function (Blueprint $table) {
            $table->dropColumn(['diagnosis', 'pre_op_medications', 'estimated_duration', 'required_tests', 'anesthesia_type']);
        });
    }
};
