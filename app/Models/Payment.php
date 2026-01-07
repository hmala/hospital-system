<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'patient_id',
        'cashier_id',
        'receipt_number',
        'amount',
        'payment_method',
        'payment_type',
        'description',
        'notes',
        'paid_at'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount' => 'decimal:2'
    ];

    // طرق الدفع المتاحة
    const PAYMENT_METHODS = [
        'cash' => 'نقدي',
        'card' => 'بطاقة',
        'insurance' => 'تأمين'
    ];

    // أنواع الدفع
    const PAYMENT_TYPES = [
        'appointment' => 'موعد',
        'lab' => 'مختبر',
        'radiology' => 'أشعة',
        'pharmacy' => 'صيدلية',
        'surgery' => 'جراحة',
        'other' => 'أخرى'
    ];

    /**
     * العلاقة مع المريض
     */
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * العلاقة مع الموعد
     */
    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * العلاقة مع الكاشير (المستخدم)
     */
    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /**
     * توليد رقم إيصال فريد
     */
    public static function generateReceiptNumber()
    {
        $date = Carbon::now()->format('Ymd');
        $lastPayment = self::whereDate('created_at', Carbon::today())
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastPayment ? (int)substr($lastPayment->receipt_number, -4) + 1 : 1;
        
        return 'REC-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * الحصول على اسم طريقة الدفع بالعربية
     */
    public function getPaymentMethodNameAttribute()
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method;
    }

    /**
     * الحصول على اسم نوع الدفع بالعربية
     */
    public function getPaymentTypeNameAttribute()
    {
        return self::PAYMENT_TYPES[$this->payment_type] ?? $this->payment_type;
    }
}
