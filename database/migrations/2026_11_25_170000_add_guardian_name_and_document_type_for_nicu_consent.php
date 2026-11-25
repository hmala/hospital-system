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
        if (! Schema::hasColumn('incubator_reservations', 'guardian_name')) {
            Schema::table('incubator_reservations', function (Blueprint $table) {
                $table->string('guardian_name')->nullable()->after('baby_name');
            });
        }

        if (! Schema::hasColumn('nicu_attachments', 'document_type')) {
            Schema::table('nicu_attachments', function (Blueprint $table) {
                $table->string('document_type', 50)->default('general')->after('title');
                $table->index(['incubator_reservation_id', 'document_type'], 'nicu_attachments_res_doc_type_idx');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('nicu_attachments', 'document_type')) {
            Schema::table('nicu_attachments', function (Blueprint $table) {
                $table->dropIndex('nicu_attachments_res_doc_type_idx');
                $table->dropColumn('document_type');
            });
        }

        if (Schema::hasColumn('incubator_reservations', 'guardian_name')) {
            Schema::table('incubator_reservations', function (Blueprint $table) {
                $table->dropColumn('guardian_name');
            });
        }
    }
};
