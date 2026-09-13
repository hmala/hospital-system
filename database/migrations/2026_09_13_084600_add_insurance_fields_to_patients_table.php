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
        if (Schema::hasTable('patients')) {
            Schema::table('patients', function (Blueprint $table) {
                if (!Schema::hasColumn('patients', 'insurance_type')) {
                    $table->string('insurance_type')->default('none')->after('insurance_number'); // none, moi, hi
                }
                if (!Schema::hasColumn('patients', 'insurance_card_no')) {
                    $table->string('insurance_card_no')->nullable()->after('insurance_type'); // رقم الباج أو هوية الضمان
                }
                if (!Schema::hasColumn('patients', 'copay_percentage')) {
                    $table->decimal('copay_percentage', 5, 2)->default(0.00)->after('insurance_card_no'); // نسبة التحمل مثل: 15.00
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('patients')) {
            Schema::table('patients', function (Blueprint $table) {
                $table->dropColumn(['insurance_type', 'insurance_card_no', 'copay_percentage']);
            });
        }
    }
};
