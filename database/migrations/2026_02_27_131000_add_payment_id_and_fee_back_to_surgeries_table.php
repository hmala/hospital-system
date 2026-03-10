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
            if (!Schema::hasColumn('surgeries', 'payment_status')) {
                $table->enum('payment_status', ['pending', 'paid', 'cancelled'])->default('pending')->after('status');
            }
            if (!Schema::hasColumn('surgeries', 'payment_id')) {
                $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null')->after('payment_status');
            }
            if (!Schema::hasColumn('surgeries', 'surgery_fee')) {
                $table->decimal('surgery_fee', 10, 2)->default(0)->after('payment_id')->comment('رسوم العملية الجراحية');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('surgeries', function (Blueprint $table) {
            if (Schema::hasColumn('surgeries', 'payment_id')) {
                $table->dropForeign(['payment_id']);
            }
            $table->dropColumn(['payment_status','payment_id','surgery_fee']);
        });
    }
};