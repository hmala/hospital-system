<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurgeryRadiologyTest extends Model
{
    use HasFactory;

    protected $fillable = [
        'surgery_id',
        'radiology_type_id',
        'status',
        'payment_status',
        'result',
        'result_file',
        'completed_at',
        'notes',
        'payment_id',
        'is_package_included',
        'included_deductible_price',
        'deduction_bearer',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'is_package_included' => 'boolean',
        'included_deductible_price' => 'decimal:2',
    ];

    public function surgery()
    {
        return $this->belongsTo(Surgery::class);
    }

    public function radiologyType()
    {
        return $this->belongsTo(RadiologyType::class);
    }

    public function radiologyService()
    {
        return $this->belongsTo(RadiologyType::class, 'radiology_type_id');
    }

    public function getStatusTextAttribute()
    {
        return match ($this->status) {
            'pending' => 'في الانتظار',
            'completed' => 'مكتمل',
            'cancelled' => 'ملغي',
            default => $this->status
        };
    }

    public function getStatusColorAttribute()
    {
        return match ($this->status) {
            'pending' => 'warning',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'secondary'
        };
    }
}
