<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NicuMedicalOrder extends Model
{
    use HasFactory;

    protected $table = 'nicu_medical_orders';

    protected $fillable = [
        'reservation_id',
        'order_type',
        'item_name',
        'lab_test_id',
        'radiology_type_id',
        'price',
        'priority',
        'instructions',
        'order_group_id',
        'result',
        'unit',
        'reference_range',
        'result_status',
        'attachment_path',
        'attachment_name',
        'technician_notes',
        'status',
        'ordered_by',
        'processed_by',
        'ordered_at',
        'completed_at',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'ordered_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    const TYPE_LAB = 'lab';
    const TYPE_RADIOLOGY = 'radiology';
    const TYPE_MEDICATION = 'medication';

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(IncubatorReservation::class, 'reservation_id');
    }

    public function labTest(): BelongsTo
    {
        return $this->belongsTo(LabTest::class, 'lab_test_id');
    }

    public function radiologyType(): BelongsTo
    {
        return $this->belongsTo(RadiologyType::class, 'radiology_type_id');
    }

    public function orderedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'ordered_by');
    }

    public function processedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function getOrderTypeNameAttribute(): string
    {
        return match($this->order_type) {
            self::TYPE_LAB => 'مختبر وتحاليل',
            self::TYPE_RADIOLOGY => 'أشعة وسونار',
            self::TYPE_MEDICATION => 'دواء ومحاليل',
            default => 'خدمة طبية',
        };
    }

    public function getPriorityTextAttribute(): string
    {
        return match($this->priority) {
            'stat' => 'عاجل جداً (STAT 🚨)',
            'urgent' => 'عاجل (Urgent)',
            default => 'عادي (Normal)',
        };
    }

    public function getPriorityBadgeClassAttribute(): string
    {
        return match($this->priority) {
            'stat' => 'bg-danger text-white',
            'urgent' => 'bg-warning text-dark',
            default => 'bg-secondary text-white',
        };
    }

    public function getStatusTextAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'قيد الانتظار',
            self::STATUS_IN_PROGRESS => 'قيد الفحص',
            self::STATUS_COMPLETED => 'مكتمل',
            self::STATUS_CANCELLED => 'ملغي',
            default => 'معلق',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'bg-warning text-dark',
            self::STATUS_IN_PROGRESS => 'bg-info text-dark',
            self::STATUS_COMPLETED => 'bg-success text-white',
            self::STATUS_CANCELLED => 'bg-danger text-white',
            default => 'bg-secondary text-white',
        };
    }
}
