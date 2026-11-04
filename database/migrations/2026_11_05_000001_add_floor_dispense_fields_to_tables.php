<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            if (! Schema::hasColumn('stock_movements', 'surgery_id')) {
                $table->foreignId('surgery_id')->nullable()->constrained('surgeries')->nullOnDelete();
            }
            if (! Schema::hasColumn('stock_movements', 'notes')) {
                $table->text('notes')->nullable();
            }
        });

        Schema::table('surgery_treatments', function (Blueprint $table) {
            if (! Schema::hasColumn('surgery_treatments', 'batch_id')) {
                $table->foreignId('batch_id')->nullable()->constrained('stock_batches')->nullOnDelete();
            }
            if (! Schema::hasColumn('surgery_treatments', 'stock_movement_id')) {
                $table->foreignId('stock_movement_id')->nullable()->constrained('stock_movements')->nullOnDelete();
            }
            if (! Schema::hasColumn('surgery_treatments', 'quantity')) {
                $table->integer('quantity')->nullable()->default(1);
            }
        });
    }

    public function down(): void
    {
        Schema::table('surgery_treatments', function (Blueprint $table) {
            if (Schema::hasColumn('surgery_treatments', 'stock_movement_id')) {
                $table->dropForeign(['stock_movement_id']);
                $table->dropColumn('stock_movement_id');
            }
            if (Schema::hasColumn('surgery_treatments', 'batch_id')) {
                $table->dropForeign(['batch_id']);
                $table->dropColumn('batch_id');
            }
            if (Schema::hasColumn('surgery_treatments', 'quantity')) {
                $table->dropColumn('quantity');
            }
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            if (Schema::hasColumn('stock_movements', 'surgery_id')) {
                $table->dropForeign(['surgery_id']);
                $table->dropColumn('surgery_id');
            }
            if (Schema::hasColumn('stock_movements', 'notes')) {
                $table->dropColumn('notes');
            }
        });
    }
};
