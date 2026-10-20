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
        if (!Schema::hasTable('nicu_medication_administrations')) {
            Schema::create('nicu_medication_administrations', function (Blueprint $table) {
                $table->engine = 'InnoDB';
                $table->id();
                $table->foreignId('reservation_id')->constrained('incubator_reservations')->cascadeOnDelete();
                $table->string('medication_name')->comment('اسم العلاج أو المادة المعطاة');
                $table->string('route', 50)->default('iv')->comment('طريقة ونوع الإعطاء: iv, oral, im, nebulizer, drops, sc, pr, topical, etc');
                $table->string('dose', 100)->comment('الجرعة المنفذة: مثلا 50 ملغ، 1 مل، 3 قطرات');
                $table->dateTime('given_at')->comment('وقت وتاريخ إعطاء الجرعة للطفل');
                $table->foreignId('nurse_id')->nullable()->constrained('users')->nullOnDelete()->comment('الممرض المنفذ للجرعة');
                $table->text('notes')->nullable()->comment('ملاحظات التمريض واستجابة الرضيع');
                $table->timestamps();

                $table->index(['reservation_id', 'given_at']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nicu_medication_administrations');
    }
};
