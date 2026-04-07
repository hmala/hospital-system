<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('stock_batches')
            ->whereNull('original_barcode')
            ->update([
                'original_barcode' => DB::raw('internal_barcode'),
                'original_received_at' => DB::raw('received_at'),
            ]);
    }

    public function down(): void
    {
        DB::table('stock_batches')
            ->whereColumn('original_barcode', 'internal_barcode')
            ->update([
                'original_barcode' => null,
                'original_received_at' => null,
            ]);
    }
};
