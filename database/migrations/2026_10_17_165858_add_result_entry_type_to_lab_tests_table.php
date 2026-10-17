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
        Schema::table('lab_tests', function (Blueprint $table) {
            if (!Schema::hasColumn('lab_tests', 'result_entry_type')) {
                $table->string('result_entry_type', 30)->default('single_value')->after('unit');
            }
        });

        // الفحوصات التي تمتلك بارامترات فرعية يتم ضبطها افتراضياً كـ attachment_only للحفاظ على التوافق الحالي
        if (Schema::hasTable('lab_test_sub_tests')) {
            \Illuminate\Support\Facades\DB::table('lab_tests')
                ->whereIn('id', function($q) {
                    $q->select('lab_test_id')->from('lab_test_sub_tests');
                })
                ->update(['result_entry_type' => 'attachment_only']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lab_tests', function (Blueprint $table) {
            if (Schema::hasColumn('lab_tests', 'result_entry_type')) {
                $table->dropColumn('result_entry_type');
            }
        });
    }
};
