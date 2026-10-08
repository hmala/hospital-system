<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (!Schema::hasColumn('appointments', 'printed_at')) {
                $table->timestamp('printed_at')->nullable()->after('completed_at');
            }
            if (!Schema::hasColumn('appointments', 'print_count')) {
                $table->unsignedInteger('print_count')->default(0)->after('printed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (Schema::hasColumn('appointments', 'printed_at')) {
                $table->dropColumn('printed_at');
            }
            if (Schema::hasColumn('appointments', 'print_count')) {
                $table->dropColumn('print_count');
            }
        });
    }
};
