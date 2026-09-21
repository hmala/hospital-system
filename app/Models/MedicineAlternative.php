<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicineAlternative extends Model
{
    use HasFactory;

    protected $table = 'medicine_alternatives';

    protected $fillable = [
        'medicine_id',
        'alternative_medicine_id',
        'notes',
    ];

    public function medicine()
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }

    public function alternativeMedicine()
    {
        return $this->belongsTo(Medicine::class, 'alternative_medicine_id');
    }
}
