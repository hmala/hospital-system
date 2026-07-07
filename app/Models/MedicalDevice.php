<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'supplier',
        'price',
        'status',
        'serial_number',
        'last_maintenance_at',
        'purchase_date'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'last_maintenance_at' => 'date',
        'purchase_date' => 'date',
    ];

    /**
     * Get the surgeries associated with the medical device.
     */
    public function surgeries()
    {
        return $this->belongsToMany(Surgery::class, 'surgery_medical_device')
                    ->withPivot('assigned_by')
                    ->withTimestamps();
    }
}
