<?php

namespace App\Models\Eye;

use App\Models\Patient;
use App\Models\StockTransferRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EyeStoreMovement extends Model
{
    use HasFactory;

    protected $table = 'eye_store_movements';

    protected $fillable = [
        'eye_store_item_id',
        'movement_type',
        'quantity',
        'balance_after',
        'stock_transfer_request_id',
        'patient_id',
        'surgery_id',
        'batch_number',
        'expiry_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'quantity'      => 'integer',
        'balance_after' => 'integer',
        'expiry_date'   => 'date',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(EyeStoreItem::class, 'eye_store_item_id');
    }

    public function transferRequest(): BelongsTo
    {
        return $this->belongsTo(StockTransferRequest::class, 'stock_transfer_request_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function surgery(): BelongsTo
    {
        return $this->belongsTo(EyeSurgery::class, 'surgery_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getMovementTypeArabicAttribute(): string
    {
        return match ($this->movement_type) {
            'direct_purchase'      => 'توريد مباشر خاص',
            'hospital_transfer_in' => 'استلام من المخزن الرئيسي',
            'surgery_dispense'     => 'صرف لعملية جراحية',
            'adjustment'           => 'تسوية جردية',
            'return'               => 'إرجاع للمخزن',
            default                => $this->movement_type
        };
    }
}
