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
        if (Schema::hasTable('emergency_lab_requests')) {
            Schema::table('emergency_lab_requests', function (Blueprint $table) {
                if (! Schema::hasColumn('emergency_lab_requests', 'details')) {
                    $table->json('details')->nullable()->after('notes');
                }
                if (! Schema::hasColumn('emergency_lab_requests', 'performed_by')) {
                    $table->foreignId('performed_by')->nullable()->after('completed_at')->constrained('users')->nullOnDelete();
                }
                if (! Schema::hasColumn('emergency_lab_requests', 'verified_by')) {
                    $table->foreignId('verified_by')->nullable()->after('performed_by')->constrained('users')->nullOnDelete();
                }
            });
        }

        if (Schema::hasTable('emergency_lab_request_tests')) {
            Schema::table('emergency_lab_request_tests', function (Blueprint $table) {
                if (! Schema::hasColumn('emergency_lab_request_tests', 'image_path')) {
                    $table->string('image_path')->nullable()->after('result');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('emergency_lab_request_tests')) {
            Schema::table('emergency_lab_request_tests', function (Blueprint $table) {
                if (Schema::hasColumn('emergency_lab_request_tests', 'image_path')) {
                    $table->dropColumn('image_path');
                }
            });
        }

        if (Schema::hasTable('emergency_lab_requests')) {
            Schema::table('emergency_lab_requests', function (Blueprint $table) {
                if (Schema::hasColumn('emergency_lab_requests', 'verified_by')) {
                    $table->dropForeign(['verified_by']);
                    $table->dropColumn('verified_by');
                }
                if (Schema::hasColumn('emergency_lab_requests', 'performed_by')) {
                    $table->dropForeign(['performed_by']);
                    $table->dropColumn('performed_by');
                }
                if (Schema::hasColumn('emergency_lab_requests', 'details')) {
                    $table->dropColumn('details');
                }
            });
        }
    }
};
