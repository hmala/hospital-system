<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('lab_results')) {
            Schema::table('lab_results', function (Blueprint $table) {
                if (!Schema::hasColumn('lab_results', 'sub_test_id')) {
                    $table->foreignId('sub_test_id')->nullable()->after('lab_test_id')->constrained('lab_test_sub_tests')->onDelete('set null');
                }
                if (!Schema::hasColumn('lab_results', 'parent_test_name')) {
                    $table->string('parent_test_name')->nullable()->after('test_name');
                }
            });

            // تعديل نوع عمود value إلى string حتى يقبل القيم النصية والرقمية (مثل Positive, Negative, 12.5)
            try {
                Schema::table('lab_results', function (Blueprint $table) {
                    $table->string('value')->nullable()->change();
                });
            } catch (\Throwable $e) {
                // Ignore if doctrine/dbal is missing or column is already compatible
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('lab_results')) {
            Schema::table('lab_results', function (Blueprint $table) {
                if (Schema::hasColumn('lab_results', 'sub_test_id')) {
                    $table->dropForeign(['sub_test_id']);
                    $table->dropColumn('sub_test_id');
                }
                if (Schema::hasColumn('lab_results', 'parent_test_name')) {
                    $table->dropColumn('parent_test_name');
                }
            });
        }
    }
};
