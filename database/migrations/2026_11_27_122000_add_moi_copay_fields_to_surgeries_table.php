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
            if (!Schema::hasColumn('surgeries', 'original_surgery_fee')) {
                $table->decimal('original_surgery_fee', 12, 2)->nullable()->after('surgery_fee');
            }
            if (!Schema::hasColumn('surgeries', 'copay_percentage')) {
                $table->decimal('copay_percentage', 5, 2)->nullable()->after('original_surgery_fee');
            }
            if (!Schema::hasColumn('surgeries', 'insurance_card_no')) {
                $table->string('insurance_card_no')->nullable()->after('insurance_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surgeries', function (Blueprint $table) {
            $cols = [];
            if (Schema::hasColumn('surgeries', 'original_surgery_fee')) $cols[] = 'original_surgery_fee';
            if (Schema::hasColumn('surgeries', 'copay_percentage')) $cols[] = 'copay_percentage';
            if (Schema::hasColumn('surgeries', 'insurance_card_no')) $cols[] = 'insurance_card_no';
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
