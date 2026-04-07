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
            $table->string('code')->nullable()->change();
        });
        
        // توليد أكواد للمنتجات القديمة التي لا تحتوي على كود
        $products = \App\Models\Product::whereNull('code')->orWhere('code', '')->get();
        foreach ($products as $product) {
            $code = 'PRD-' . str_pad($product->id, 4, '0', STR_PAD_LEFT);
            
            // التأكد من عدم تكرار الكود
            $suffix = 1;
            $originalCode = $code;
            while (\App\Models\Product::where('code', $code)->where('id', '!=', $product->id)->exists()) {
                $code = $originalCode . '-' . $suffix;
                $suffix++;
            }
            
            $product->code = $code;
            $product->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('code')->nullable(false)->change();
        });
    }
};
