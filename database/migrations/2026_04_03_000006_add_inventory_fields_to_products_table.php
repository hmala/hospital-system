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
        Schema::table('products', function (Blueprint $table) {
            $table->string('category')->nullable()->after('code');
            $table->text('description')->nullable()->after('alert_quantity');
            $table->integer('reorder_level')->default(0)->after('alert_quantity');
            $table->decimal('cost_price', 15, 2)->default(0)->after('reorder_level');
            $table->decimal('selling_price', 15, 2)->default(0)->after('cost_price');
            $table->integer('safety_stock')->default(0)->after('selling_price');
            $table->string('storage_conditions')->nullable()->after('safety_stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'category',
                'description',
                'reorder_level',
                'cost_price',
                'selling_price',
                'safety_stock',
                'storage_conditions',
            ]);
        });
    }
};
