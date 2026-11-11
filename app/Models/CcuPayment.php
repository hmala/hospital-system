<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CcuPayment extends Model
{
    use HasFactory;

    protected $table = 'ccu_payments';

    protected $fillable = [
        'icu_admission_id',
        'patient_id',
        'receipt_number',
        'payment_type',
        'amount',
        'payment_method',
        'financial_status',
        'cashier_id',
        'notes',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public const TYPE_DEPOSIT = 'deposit';

    public const TYPE_INTERIM = 'interim';

    public const TYPE_FINAL = 'final_settlement';

    public const TYPE_REFUND = 'refund';

    public const PAYMENT_TYPES = [
        self::TYPE_DEPOSIT => 'دفعة أمانات (تأمين مسبق)',
        self::TYPE_INTERIM => 'دفعة تحت الحساب',
        self::TYPE_FINAL => 'تسديد وتصفية نهائية',
        self::TYPE_REFUND => 'صرف واسترجاع فائض الرسوم',
    ];

    public const PAYMENT_METHODS = [
        'cash' => 'نقدي (كاش)',
        'card' => 'دفع إلكتروني (بطاقة)',
    ];

    public function admission(): BelongsTo
    {
        return $this->belongsTo(IcuAdmission::class, 'icu_admission_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function getPaymentTypeNameAttribute(): string
    {
        return self::PAYMENT_TYPES[$this->payment_type] ?? 'سند عناية مركزة';
    }

    public function getPaymentTypeBadgeAttribute(): string
    {
        return match ($this->payment_type) {
            self::TYPE_DEPOSIT => '<span class="badge bg-info text-dark"><i class="fas fa-hand-holding-usd me-1"></i>أمانات سلفة</span>',
            self::TYPE_INTERIM => '<span class="badge bg-primary"><i class="fas fa-money-bill-wave me-1"></i>دفعة تحت الحساب</span>',
            self::TYPE_FINAL => '<span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>تسوية نهائية</span>',
            self::TYPE_REFUND => '<span class="badge bg-warning text-dark"><i class="fas fa-undo me-1"></i>استرجاع فائض</span>',
            default => '<span class="badge bg-secondary">سند قبض</span>',
        };
    }

    public function getFormattedAmountAttribute(): string
    {
        return number_format((float) $this->amount, 0).' د.ع';
    }

    /**
     * توليد رقم وصل رسمي فريد لمدفوعات وأمانات العناية المركزة
     */
    public static function generateReceiptNumber(): string
    {
        $datePrefix = date('Ymd');
        $random = strtoupper(Str::random(4));
        $count = static::whereDate('created_at', today())->count() + 1;

        return 'CCU-'.$datePrefix.'-'.str_pad($count, 3, '0', STR_PAD_LEFT).'-'.$random;
    }
}
