<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // إضافة pending_service_selection إلى enum status في جدول visits
        DB::statement("ALTER TABLE visits MODIFY COLUMN status ENUM('scheduled', 'in_progress', 'completed', 'cancelled', 'pending_payment', 'pending_service_selection') DEFAULT 'scheduled'");
        
        // إضافة pending_service_selection إلى enum status في جدول requests
        DB::statement("ALTER TABLE requests MODIFY COLUMN status ENUM('pending', 'in_progress', 'completed', 'cancelled', 'pending_service_selection') DEFAULT 'pending'");
        
        // إضافة not_applicable إلى enum payment_status في جدول requests
        DB::statement("ALTER TABLE requests MODIFY COLUMN payment_status ENUM('pending', 'paid', 'not_applicable') DEFAULT 'pending'");
    }

    public function down(): void
    {
        // إرجاع enum status في جدول visits إلى حالته السابقة
        DB::statement("ALTER TABLE visits MODIFY COLUMN status ENUM('scheduled', 'in_progress', 'completed', 'cancelled', 'pending_payment') DEFAULT 'scheduled'");
        
        // إرجاع enum status في جدول requests إلى حالته السابقة
        DB::statement("ALTER TABLE requests MODIFY COLUMN status ENUM('pending', 'in_progress', 'completed', 'cancelled') DEFAULT 'pending'");
        
        // إرجاع enum payment_status في جدول requests إلى حالته السابقة
        DB::statement("ALTER TABLE requests MODIFY COLUMN payment_status ENUM('pending', 'paid') DEFAULT 'pending'");
    }
};
