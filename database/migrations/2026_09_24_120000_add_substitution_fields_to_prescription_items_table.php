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
        Schema::table('prescription_items', function (Blueprint $table) {
            $table->foreignId('suggested_medicine_id')->nullable()->after('dispensed_medicine_id')->constrained('medicines')->nullOnDelete();
            $table->string('substitution_status', 30)->default('none')->after('suggested_medicine_id')->comment('none, pending_approval, approved, rejected');
            $table->text('substitution_reason')->nullable()->after('substitution_status');
            $table->text('substitution_response_notes')->nullable()->after('substitution_reason');
            $table->timestamp('substitution_responded_at')->nullable()->after('substitution_response_notes');

            $table->index(['prescription_id', 'substitution_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('prescription_items', function (Blueprint $table) {
            $table->dropForeign(['suggested_medicine_id']);
            $table->dropIndex(['prescription_id', 'substitution_status']);
            $table->dropColumn([
                'suggested_medicine_id',
                'substitution_status',
                'substitution_reason',
                'substitution_response_notes',
                'substitution_responded_at'
            ]);
        });
    }
};
