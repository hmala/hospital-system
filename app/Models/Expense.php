<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'amount',
        'category',
        'payment_method',
        'expense_date',
        'reference_number',
        'vendor',
        'receipt_path',
        'created_by',
        'approved_by',
        'status',
        'notes',
    ];

    protected $casts = [
        'expense_date' => 'date',
        'amount' => 'decimal:2',
    ];

    // فئات المصروفات
    const CATEGORIES = [
        'salary'      => 'رواتب وأجور',
        'maintenance' => 'صيانة',
        'supplies'    => 'مستلزمات طبية',
        'utilities'   => 'خدمات (كهرباء، ماء، إنترنت)',
        'equipment'   => 'معدات وأجهزة',
        'rent'        => 'إيجار',
        'insurance'   => 'تأمين',
        'other'       => 'أخرى',
    ];

    // طرق الدفع
    const PAYMENT_METHODS = [
        'cash'          => 'نقدي',
        'card'          => 'بطاقة',
        'bank_transfer' => 'تحويل بنكي',
        'check'         => 'شيك',
    ];

    // حالات المصروف
    const STATUSES = [
        'pending'  => 'معلق',
        'approved' => 'موافق عليه',
        'rejected' => 'مرفوض',
    ];

    /**
     * العلاقة مع المستخدم المنشئ
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * العلاقة مع المستخدم الموافق
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * الحصول على اسم الفئة بالعربية
     */
    public function getCategoryNameAttribute()
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /**
     * الحصول على اسم طريقة الدفع بالعربية
     */
    public function getPaymentMethodNameAttribute()
    {
        return self::PAYMENT_METHODS[$this->payment_method] ?? $this->payment_method;
    }

    /**
     * الحصول على اسم الحالة بالعربية
     */
    public function getStatusNameAttribute()
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
