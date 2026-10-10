<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_type',
        'category',
        'voucher_type',
        'voucher_number',
        'related_type',
        'related_id',
        'amount',
        'currency',
        'payment_method',
        'description',
        'notes',
        'performed_by_id',
        'performed_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'performed_at' => 'datetime',
    ];

    const CATEGORIES = [
        // المقبوضات (Inflows)
        'consultation' => 'إيراد كشفية العيادات الاستشارية',
        'emergency' => 'إيراد خدمات الطوارئ',
        'lab' => 'إيراد الفحوصات المختبرية',
        'radiology' => 'إيراد الأشعة والتصوير',
        'surgery' => 'إيراد العمليات الجراحية',
        'general_income' => 'إيرادات وتحصيلات عامة',

        // المصروفات (Outflows)
        'doctor_payout' => 'صرف مستحقات أطباء',
        'purchase' => 'فواتير مشتريات ومورّدين',
        'salary' => 'رواتب ومكافآت موظفين',
        'maintenance' => 'صيانة وأجهزة طبية',
        'utilities' => 'كهرباء، ماء، محروقات ومولدات',
        'medical_supplies' => 'مستلزمات ومستهلكات طبية',
        'operational' => 'نثريات ومصاريف تشغيلية',
        'other' => 'أخرى',
    ];

    const PAYMENT_METHODS = [
        'cash' => 'نقدي (صندوق الخزينة)',
        'bank_transfer' => 'تحويل بنكي / مصرفي',
        'cheque' => 'صك بنكي',
        'card' => 'دفع إلكتروني / بطاقة',
    ];

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by_id');
    }

    public function related()
    {
        return $this->morphTo();
    }

    public function isInflow(): bool
    {
        return $this->voucher_type === 'inflow' || in_array($this->transaction_type, ['hospital_revenue', 'receivable']);
    }

    public function isOutflow(): bool
    {
        return $this->voucher_type === 'outflow' || in_array($this->transaction_type, ['expense', 'doctor_payment', 'payable']);
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? ($this->category ?: 'عام');
    }

    public function getPaymentMethodLabelAttribute(): string
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? ($this->payment_method ?: 'نقدي');
    }

    public function scopeInflow($query)
    {
        return $query->where(function ($q) {
            $q->where('voucher_type', 'inflow')
              ->orWhereIn('transaction_type', ['hospital_revenue', 'receivable']);
        });
    }

    public function scopeOutflow($query)
    {
        return $query->where(function ($q) {
            $q->where('voucher_type', 'outflow')
              ->orWhereIn('transaction_type', ['expense', 'doctor_payment', 'payable']);
        });
    }

    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}

