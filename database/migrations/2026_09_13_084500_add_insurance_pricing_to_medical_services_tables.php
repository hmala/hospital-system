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
        // 1. فئات التحاليل المخبرية (Lab Tests)
        if (Schema::hasTable('lab_tests')) {
            Schema::table('lab_tests', function (Blueprint $table) {
                if (!Schema::hasColumn('lab_tests', 'moi_price')) {
                    $table->decimal('moi_price', 10, 2)->nullable()->after('price');
                }
                if (!Schema::hasColumn('lab_tests', 'is_moi_active')) {
                    $table->boolean('is_moi_active')->default(true)->after('moi_price');
                }
                if (!Schema::hasColumn('lab_tests', 'hi_price')) {
                    $table->decimal('hi_price', 10, 2)->nullable()->after('is_moi_active');
                }
                if (!Schema::hasColumn('lab_tests', 'is_hi_active')) {
                    $table->boolean('is_hi_active')->default(true)->after('hi_price');
                }
            });
        }

        // 2. أنواع الأشعة والسونار والمفراس (Radiology Types)
        if (Schema::hasTable('radiology_types')) {
            Schema::table('radiology_types', function (Blueprint $table) {
                if (!Schema::hasColumn('radiology_types', 'moi_price')) {
                    $table->decimal('moi_price', 10, 2)->nullable()->after('base_price');
                }
                if (!Schema::hasColumn('radiology_types', 'is_moi_active')) {
                    $table->boolean('is_moi_active')->default(true)->after('moi_price');
                }
                if (!Schema::hasColumn('radiology_types', 'hi_price')) {
                    $table->decimal('hi_price', 10, 2)->nullable()->after('is_moi_active');
                }
                if (!Schema::hasColumn('radiology_types', 'is_hi_active')) {
                    $table->boolean('is_hi_active')->default(true)->after('hi_price');
                }
            });
        }

        // 3. خدمات وأسعار الطوارئ (Emergency Services)
        if (Schema::hasTable('emergency_services')) {
            Schema::table('emergency_services', function (Blueprint $table) {
                if (!Schema::hasColumn('emergency_services', 'moi_price')) {
                    $table->decimal('moi_price', 10, 2)->nullable()->after('price');
                }
                if (!Schema::hasColumn('emergency_services', 'is_moi_active')) {
                    $table->boolean('is_moi_active')->default(true)->after('moi_price');
                }
                if (!Schema::hasColumn('emergency_services', 'hi_price')) {
                    $table->decimal('hi_price', 10, 2)->nullable()->after('is_moi_active');
                }
                if (!Schema::hasColumn('emergency_services', 'is_hi_active')) {
                    $table->boolean('is_hi_active')->default(true)->after('hi_price');
                }
            });
        }

        // 4. كشفية الأطباء الاستشاريين (Doctors)
        if (Schema::hasTable('doctors')) {
            Schema::table('doctors', function (Blueprint $table) {
                if (!Schema::hasColumn('doctors', 'moi_price')) {
                    $table->decimal('moi_price', 10, 2)->nullable()->after('consultation_fee');
                }
                if (!Schema::hasColumn('doctors', 'is_moi_active')) {
                    $table->boolean('is_moi_active')->default(true)->after('moi_price');
                }
                if (!Schema::hasColumn('doctors', 'hi_price')) {
                    $table->decimal('hi_price', 10, 2)->nullable()->after('is_moi_active');
                }
                if (!Schema::hasColumn('doctors', 'is_hi_active')) {
                    $table->boolean('is_hi_active')->default(true)->after('hi_price');
                }
            });
        }

        // 5. كشفية العيادات والأقسام (Departments)
        if (Schema::hasTable('departments')) {
            Schema::table('departments', function (Blueprint $table) {
                if (!Schema::hasColumn('departments', 'moi_price')) {
                    $table->decimal('moi_price', 10, 2)->nullable()->after('consultation_fee');
                }
                if (!Schema::hasColumn('departments', 'is_moi_active')) {
                    $table->boolean('is_moi_active')->default(true)->after('moi_price');
                }
                if (!Schema::hasColumn('departments', 'hi_price')) {
                    $table->decimal('hi_price', 10, 2)->nullable()->after('is_moi_active');
                }
                if (!Schema::hasColumn('departments', 'is_hi_active')) {
                    $table->boolean('is_hi_active')->default(true)->after('hi_price');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('lab_tests')) {
            Schema::table('lab_tests', function (Blueprint $table) {
                $table->dropColumn(['moi_price', 'is_moi_active', 'hi_price', 'is_hi_active']);
            });
        }

        if (Schema::hasTable('radiology_types')) {
            Schema::table('radiology_types', function (Blueprint $table) {
                $table->dropColumn(['moi_price', 'is_moi_active', 'hi_price', 'is_hi_active']);
            });
        }

        if (Schema::hasTable('emergency_services')) {
            Schema::table('emergency_services', function (Blueprint $table) {
                $table->dropColumn(['moi_price', 'is_moi_active', 'hi_price', 'is_hi_active']);
            });
        }

        if (Schema::hasTable('doctors')) {
            Schema::table('doctors', function (Blueprint $table) {
                $table->dropColumn(['moi_price', 'is_moi_active', 'hi_price', 'is_hi_active']);
            });
        }

        if (Schema::hasTable('departments')) {
            Schema::table('departments', function (Blueprint $table) {
                $table->dropColumn(['moi_price', 'is_moi_active', 'hi_price', 'is_hi_active']);
            });
        }
    }
};
