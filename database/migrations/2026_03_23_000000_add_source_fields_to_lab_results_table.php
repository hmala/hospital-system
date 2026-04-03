<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lab_results', function (Blueprint $table) {
            $table->string('source_type')->default('general')->after('notes');
            $table->foreignId('package_id')->nullable()->constrained('packages')->onDelete('set null')->after('source_type');
            $table->foreignId('lab_test_id')->nullable()->constrained('lab_tests')->onDelete('set null')->after('package_id');
        });
    }

    public function down(): void
    {
        Schema::table('lab_results', function (Blueprint $table) {
            $table->dropForeign(['package_id']);
            $table->dropColumn('package_id');
            $table->dropForeign(['lab_test_id']);
            $table->dropColumn('lab_test_id');
            $table->dropColumn('source_type');
        });
    }
};