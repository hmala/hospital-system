<?php

use App\Models\Doctor;
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
        Schema::table('doctors', function (Blueprint $table) {
            if (! Schema::hasColumn('doctors', 'direct_consultation_fee')) {
                $table->decimal('direct_consultation_fee', 10, 2)
                    ->nullable()
                    ->after('consultation_fee')
                    ->comment('أجر الكشفية المباشرة / استشارة الطوارئ والرقود المعتمدة');
            }
        });

        // تهيئة الطبيب د. نورس ناصر الحميري برسم كشفية مباشرة 30,000 د.ع لحالته المعتمدة
        $nawres = Doctor::where('id', 160)->first();
        if ($nawres && is_null($nawres->direct_consultation_fee)) {
            $nawres->update(['direct_consultation_fee' => 30000.00]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            if (Schema::hasColumn('doctors', 'direct_consultation_fee')) {
                $table->dropColumn('direct_consultation_fee');
            }
        });
    }
};
