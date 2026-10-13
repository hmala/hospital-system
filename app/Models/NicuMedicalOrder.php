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
        'price',
        'priority',
        'instructions',
        'result',
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
}
