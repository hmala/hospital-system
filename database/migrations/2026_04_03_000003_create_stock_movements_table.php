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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('stock_batches'); // من أي وجبة بالتحديد؟
            $table->foreignId('from_location_id')->constrained('locations');
            $table->foreignId('to_location_id')->nullable()->constrained('locations');
            $table->integer('quantity');
            $table->enum('type', ['purchase', 'transfer', 'issue_to_patient', 'return', 'damage']);
            $table->foreignId('user_id')->constrained('users'); // من الموظف الذي قام بالحركة؟
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
