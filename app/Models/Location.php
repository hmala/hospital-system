<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'custodian_id',
    ];

    public function custodian()
    {
        return $this->belongsTo(User::class, 'custodian_id');
    }

    public function staff()
    {
        return $this->hasMany(User::class, 'location_id');
    }

    public function custodianHistories()
    {
        return $this->hasMany(LocationCustodianHistory::class)->latest('assigned_at');
    }

    public function stockMovementsFrom()
    {
        return $this->hasMany(StockMovement::class, 'from_location_id');
    }

    public function stockMovementsTo()
    {
        return $this->hasMany(StockMovement::class, 'to_location_id');
    }

    public function stockBatches()
    {
        return $this->hasMany(StockBatch::class);
    }

    public function productThresholds()
    {
        return $this->hasMany(LocationProductThreshold::class);
    }

    public function medicalDevices()
    {
        return $this->hasMany(MedicalDevice::class);
    }
}
