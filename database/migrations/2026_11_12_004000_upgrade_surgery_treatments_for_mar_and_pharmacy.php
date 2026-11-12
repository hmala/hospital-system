<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surgery_treatments', function (Blueprint $table) {
            if (! Schema::hasColumn('surgery_treatments', 'medicine_id')) {
                $table->foreignId('medicine_id')
                    ->nullable()
                    ->after('item_type')
                    ->constrained('medicines')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('surgery_treatments', 'prescribed_by')) {
                $table->foreignId('prescribed_by')
                    ->nullable()
                    ->after('medicine_id')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('surgery_treatments', 'route')) {
                $table->string('route', 50)->nullable()->after('dosage');
            }

            if (! Schema::hasColumn('surgery_treatments', 'frequency_mode')) {
                $table->string('frequency_mode', 30)->default('scheduled')->after('timing');
            }

            if (! Schema::hasColumn('surgery_treatments', 'interval_hours')) {
                $table->unsignedSmallInteger('interval_hours')->nullable()->after('frequency_mode');
            }

            if (! Schema::hasColumn('surgery_treatments', 'start_at')) {
                $table->timestamp('start_at')->nullable()->after('duration_unit');
            }

            if (! Schema::hasColumn('surgery_treatments', 'total_doses')) {
                $table->unsignedSmallInteger('total_doses')->nullable()->after('start_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('surgery_treatments', function (Blueprint $table) {
            if (Schema::hasColumn('surgery_treatments', 'medicine_id')) {
                $table->dropForeign(['medicine_id']);
                $table->dropColumn('medicine_id');
            }

            if (Schema::hasColumn('surgery_treatments', 'prescribed_by')) {
                $table->dropForeign(['prescribed_by']);
                $table->dropColumn('prescribed_by');
            }

            foreach (['route', 'frequency_mode', 'interval_hours', 'start_at', 'total_doses'] as $column) {
                if (Schema::hasColumn('surgery_treatments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
