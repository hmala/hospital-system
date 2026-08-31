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
        'surgery_id',
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
     * العلاقة مع العملية الجراحية
     */
    public function surgery()
    {
        return $this->belongsTo(Surgery::class);
    }

    /**
     * العلاقة مع الكاشير (المستخدم)
     */
    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    /**
     * العلاقة مع إيراد الاستشارة
     */
    public function consultationRevenue()
    {
        return $this->hasOne(ConsultationRevenue::class);
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

    /**
     * الحصول على اسم الخدمة التفصيلية المقدمة
     */
    public function getServiceNameAttribute()
    {
        // 1. كشفية عيادة / استشارية
        if ($this->payment_type === 'appointment' || $this->appointment_id) {
            $dept = optional(optional($this->appointment)->department)->name;
            $doc = optional(optional(optional($this->appointment)->doctor)->user)->name;
            $items = [];
            if ($dept) $items[] = $dept;
            if ($doc) $items[] = 'د. ' . $doc;
            return !empty($items) ? implode(' - ', $items) : 'كشفية عيادة استشارية';
        }

        // 2. تحاليل المختبر أو الأشعة من جدول requests
        if ($this->request_id && $this->request) {
            $req = $this->request;
            $details = is_array($req->details) ? $req->details : json_decode($req->details, true);
            if ($req->type === 'lab') {
                if (!empty($details['lab_test_ids'])) {
                    $names = \App\Models\LabTest::whereIn('id', $details['lab_test_ids'])->pluck('name')->toArray();
                    if (!empty($names)) return implode('، ', $names);
                } elseif (!empty($details['tests'])) {
                    return is_array($details['tests']) ? implode('، ', $details['tests']) : (string)$details['tests'];
                }
                return 'تحاليل مختبرية';
            } elseif ($req->type === 'radiology') {
                if (!empty($details['radiology_type_ids'])) {
                    $names = \App\Models\RadiologyType::whereIn('id', $details['radiology_type_ids'])->pluck('name')->toArray();
                    if (!empty($names)) return implode('، ', $names);
                } elseif (!empty($details['radiology_types'])) {
                    $names = \App\Models\RadiologyType::whereIn('id', $details['radiology_types'])->pluck('name')->toArray();
                    if (!empty($names)) return implode('، ', $names);
                }
                return 'فحص أشعة وتصوير';
            }
        }

        // 3. طوارئ
        if ($this->payment_type === 'emergency' || $this->emergency_id) {
            if ($this->description && (str_contains($this->description, 'خدمات') || str_contains($this->description, 'تحاليل'))) {
                return $this->description;
            }
            if ($this->emergency && $this->emergency->services->isNotEmpty()) {
                return $this->emergency->services->pluck('name')->implode('، ');
            }
            return $this->description ?: 'خدمات ومعاينة طوارئ';
        }

        // 4. عمليات جراحية
        if ($this->payment_type === 'surgery' || $this->surgery_id) {
            $surgeryType = optional($this->surgery)->surgery_type;
            if ($surgeryType) {
                return $surgeryType;
            }
            return $this->description ?: 'عملية جراحية';
        }

        // 5. الوصف الافتراضي
        return $this->description ?: $this->payment_type_name;
    }
}
