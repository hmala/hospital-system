<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // توحيد أنواع الحاضنات القديمة (oxygen, phototherapy) إلى حاضنة اعتيادية normal
        // والإبقاء فقط على نوعين رئيسيين: حاضنة (normal) وكبسولة علاج ضوئي (capsule)
        DB::table('incubators')
            ->whereIn('incubator_type', ['oxygen', 'phototherapy'])
            ->update(['incubator_type' => 'normal']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};
