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
        Schema::create('lab_test_references', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_test_id')->constrained('lab_tests')->onDelete('cascade');
            $table->enum('gender', ['male', 'female', 'both'])->default('both');
            $table->unsignedSmallInteger('age_min')->default(0)->comment('بالسنوات');
            $table->unsignedSmallInteger('age_max')->default(999)->comment('بالسنوات، 999 = بلا حد');
            $table->decimal('ref_min', 10, 3)->nullable()->comment('الحد الأدنى الرقمي');
            $table->decimal('ref_max', 10, 3)->nullable()->comment('الحد الأعلى الرقمي');
            $table->string('ref_text', 100)->nullable()->comment('نص المرجع مثل: Negative أو < 200');
            $table->string('unit', 50)->nullable()->comment('الوحدة الخاصة بهذا المدى إن اختلفت');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['lab_test_id', 'gender', 'age_min', 'age_max']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_test_references');
    }
};
