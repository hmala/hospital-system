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
        'request_id',
        'emergency_id',
        'patient_id',
        'cashier_id',
        'receipt_number',
        'amount',
        'payment_method',
        'payment_type',
        'description',
        'notes',
        'is_inclusive',
        'paid_at'
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'amount' => 'decimal:2',
        'is_inclusive' => 'boolean'
    ];

    // طرق الدفع المتاحة
    const PAYMENT_METHODS = [
        'pending' => 'معلق',
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
        'emergency' => 'طوارئ',
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
     * العلاقة مع الطلب الطبي
     */
    public function request()
    {
        return $this->belongsTo(Request::class, 'request_id');
    }

    /**
     * العلاقة مع حالة الطوارئ
     */
    public function emergency()
    {
        return $this->belongsTo(Emergency::class);
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
        
        // البحث عن آخر رقم إيصال بنفس التاريخ
        $lastPayment = self::where('receipt_number', 'like', 'REC-' . $date . '-%')
            ->orderBy('receipt_number', 'desc')
            ->first();
        
        if ($lastPayment && $lastPayment->receipt_number) {
            $sequence = (int)substr($lastPayment->receipt_number, -4) + 1;
        } else {
            $sequence = 1;
        }
        
        // التحقق من عدم وجود الرقم مسبقاً (حماية إضافية)
        do {
            $receiptNumber = 'REC-' . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
            $exists = self::where('receipt_number', $receiptNumber)->exists();
            if ($exists) {
                $sequence++;
            }
        } while ($exists);
        
        return $receiptNumber;
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
