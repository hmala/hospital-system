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
        if (Schema::hasTable('patients')) {
            Schema::table('patients', function (Blueprint $table) {
                if (!Schema::hasColumn('patients', 'telegram_chat_id')) {
                    $table->string('telegram_chat_id')->nullable()->index()->after('notes');
                }
                if (!Schema::hasColumn('patients', 'telegram_username')) {
                    $table->string('telegram_username')->nullable()->after('telegram_chat_id');
                }
            });
        }

        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if (!Schema::hasColumn('appointments', 'telegram_chat_id')) {
                    $table->string('telegram_chat_id')->nullable()->index()->after('status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('patients')) {
            Schema::table('patients', function (Blueprint $table) {
                if (Schema::hasColumn('patients', 'telegram_chat_id')) {
                    $table->dropColumn(['telegram_chat_id', 'telegram_username']);
                }
            });
        }

        if (Schema::hasTable('appointments')) {
            Schema::table('appointments', function (Blueprint $table) {
                if (Schema::hasColumn('appointments', 'telegram_chat_id')) {
                    $table->dropColumn('telegram_chat_id');
                }
            });
        }
    }
};
