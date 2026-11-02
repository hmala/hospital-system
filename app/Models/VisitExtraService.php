<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisitExtraService extends Model
{
    use HasFactory;

    protected $table = 'visit_extra_services';

    protected $fillable = [
        'visit_id',
        'request_id',
        'service_id',
        'name',
        'price',
        'quantity',
        'total_amount',
        'notes',
        'payment_status',
        'payment_id',
        'created_by',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'total_amount' => 'decimal:2',
    ];

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(Request::class, 'request_id');
    }

    public function service(): BelongsTo
    {
        return $this->belongsTo(HospitalExtraService::class, 'service_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
