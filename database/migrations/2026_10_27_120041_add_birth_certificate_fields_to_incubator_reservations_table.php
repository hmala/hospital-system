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
        if (Schema::hasTable('incubator_reservations')) {
            Schema::table('incubator_reservations', function (Blueprint $table) {
                if (! Schema::hasColumn('incubator_reservations', 'is_independent_patient')) {
                    $table->boolean('is_independent_patient')->default(false)->after('baby_name')->comment('هل المريض المسجل هو الطفل نفسه بشكل مستقل؟');
                }
                if (! Schema::hasColumn('incubator_reservations', 'birth_certificate_no')) {
                    $table->string('birth_certificate_no', 100)->nullable()->after('is_independent_patient')->comment('رقم بيان الولادة الرسمي');
                }
                if (! Schema::hasColumn('incubator_reservations', 'birth_certificate_date')) {
                    $table->date('birth_certificate_date')->nullable()->after('birth_certificate_no')->comment('تاريخ إصدار بيان الولادة');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('incubator_reservations')) {
            Schema::table('incubator_reservations', function (Blueprint $table) {
                $columns = [];
                if (Schema::hasColumn('incubator_reservations', 'birth_certificate_date')) {
                    $columns[] = 'birth_certificate_date';
                }
                if (Schema::hasColumn('incubator_reservations', 'birth_certificate_no')) {
                    $columns[] = 'birth_certificate_no';
                }
                if (Schema::hasColumn('incubator_reservations', 'is_independent_patient')) {
                    $columns[] = 'is_independent_patient';
                }
                if (! empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
