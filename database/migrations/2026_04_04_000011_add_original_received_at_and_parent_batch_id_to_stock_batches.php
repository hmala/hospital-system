<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            $table->timestamp('original_received_at')->nullable()->after('received_at');
            $table->foreignId('parent_batch_id')->nullable()->constrained('stock_batches')->nullOnDelete()->after('original_received_at');
        });
    }

    public function down()
    {
        Schema::table('stock_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_batch_id');
            $table->dropColumn('original_received_at');
        });
    }
};
