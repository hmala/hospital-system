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
        Schema::table('surgery_consultations', function (Blueprint $table) {
            if (! Schema::hasColumn('surgery_consultations', 'payment_status')) {
                $table->string('payment_status', 30)->default('pending')->after('fee');
            }
            if (! Schema::hasColumn('surgery_consultations', 'payment_id')) {
                $table->unsignedBigInteger('payment_id')->nullable()->after('payment_status');
            }
            if (! Schema::hasColumn('surgery_consultations', 'is_package_included')) {
                $table->boolean('is_package_included')->default(false)->after('payment_id');
            }
            if (! Schema::hasColumn('surgery_consultations', 'patient_share')) {
                $table->decimal('patient_share', 12, 2)->default(0.00)->after('is_package_included');
            }
            if (! Schema::hasColumn('surgery_consultations', 'insurance_share')) {
                $table->decimal('insurance_share', 12, 2)->default(0.00)->after('patient_share');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surgery_consultations', function (Blueprint $table) {
            $table->dropColumn([
                'payment_status',
                'payment_id',
                'is_package_included',
                'patient_share',
                'insurance_share',
            ]);
        });
    }
};
