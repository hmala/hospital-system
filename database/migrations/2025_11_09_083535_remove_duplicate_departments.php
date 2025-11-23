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
        // إزالة العيادات المكررة والاحتفاظ بالأولى من كل نوع
        $departmentTypes = ['internal', 'surgery', 'pediatrics', 'obstetrics'];

        foreach ($departmentTypes as $type) {
            // الحصول على ID العيادة الأولى من هذا النوع
            $firstDepartment = DB::table('departments')
                ->where('type', $type)
                ->orderBy('id')
                ->first();

            if ($firstDepartment) {
                // حذف جميع العيادات من نفس النوع ما عدا الأولى
                DB::table('departments')
                    ->where('type', $type)
                    ->where('id', '!=', $firstDepartment->id)
                    ->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // لا يمكن التراجع عن هذه العملية
    }
};
