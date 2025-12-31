<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SurgeryTreatment extends Model
{
    use HasFactory;

    protected $fillable = [
        'surgery_id',
        'description',
        'dosage',
        'timing',
        'duration_value',
        'duration_unit',
        'sort_order',
    ];

    public function surgery()
    {
        return $this->belongsTo(Surgery::class);
    }
}
