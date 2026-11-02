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
        $this->addColumnsToConnection(null);

        try {
            if (config('database.connections.remote_hostinger')) {
                $this->addColumnsToConnection('remote_hostinger');
            }
        } catch (\Throwable $e) {
            // تجاهل في حالة عدم الاتصال
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropColumnsFromConnection(null);

        try {
            if (config('database.connections.remote_hostinger')) {
                $this->dropColumnsFromConnection('remote_hostinger');
            }
        } catch (\Throwable $e) {
            // تجاهل
        }
    }

    protected function addColumnsToConnection(?string $connection): void
    {
        $schema = $connection ? Schema::connection($connection) : Schema::connection(config('database.default'));

        if ($schema->hasTable('payments')) {
            $schema->table('payments', function (Blueprint $table) use ($schema) {
                if (! $schema->hasColumn('payments', 'discount_percentage')) {
                    $table->decimal('discount_percentage', 5, 2)->default(0.00)->after('copay_percentage');
                }
                if (! $schema->hasColumn('payments', 'discount_amount')) {
                    $table->decimal('discount_amount', 12, 2)->default(0.00)->after('discount_percentage');
                }
                if (! $schema->hasColumn('payments', 'discount_reason')) {
                    $table->string('discount_reason', 255)->nullable()->after('discount_amount');
                }
                if (! $schema->hasColumn('payments', 'discount_by')) {
                    $table->unsignedBigInteger('discount_by')->nullable()->after('discount_reason');
                }
            });
        }
    }

    protected function dropColumnsFromConnection(?string $connection): void
    {
        $schema = $connection ? Schema::connection($connection) : Schema::connection(config('database.default'));

        if ($schema->hasTable('payments')) {
            $schema->table('payments', function (Blueprint $table) use ($schema) {
                $cols = [];
                if ($schema->hasColumn('payments', 'discount_by')) {
                    $cols[] = 'discount_by';
                }
                if ($schema->hasColumn('payments', 'discount_reason')) {
                    $cols[] = 'discount_reason';
                }
                if ($schema->hasColumn('payments', 'discount_amount')) {
                    $cols[] = 'discount_amount';
                }
                if ($schema->hasColumn('payments', 'discount_percentage')) {
                    $cols[] = 'discount_percentage';
                }
                if (! empty($cols)) {
                    $table->dropColumn($cols);
                }
            });
        }
    }
};
