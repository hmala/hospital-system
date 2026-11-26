<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('surgery_treatments')) {
            Schema::table('surgery_treatments', function (Blueprint $table) {
                if (! Schema::hasColumn('surgery_treatments', 'prescription_item_id')) {
                    $table->foreignId('prescription_item_id')
                        ->nullable()
                        ->after('medicine_id')
                        ->constrained('prescription_items')
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn('surgery_treatments', 'pharmacy_status')) {
                    $table->string('pharmacy_status', 30)
                        ->nullable()
                        ->default('sent_to_pharmacy')
                        ->after('status');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('surgery_treatments')) {
            Schema::table('surgery_treatments', function (Blueprint $table) {
                if (Schema::hasColumn('surgery_treatments', 'prescription_item_id')) {
                    $table->dropForeign(['prescription_item_id']);
                    $table->dropColumn('prescription_item_id');
                }

                if (Schema::hasColumn('surgery_treatments', 'pharmacy_status')) {
                    $table->dropColumn('pharmacy_status');
                }
            });
        }
    }
};
