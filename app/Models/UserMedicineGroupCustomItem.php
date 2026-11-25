<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMedicineGroupCustomItem extends Model
{
    use HasFactory;

    protected $table = 'user_medicine_group_custom_items';

    protected $fillable = [
        'group_id',
        'nicu_custom_medicine_id',
        'medicine_name',
        'dosage_form',
        'dosage',
        'route',
        'frequency',
        'duration',
        'duration_days',
        'instructions',
    ];

    protected function casts(): array
    {
        return [
            'duration_days' => 'integer',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(UserMedicineGroup::class, 'group_id');
    }

    public function customMedicine(): BelongsTo
    {
        return $this->belongsTo(NicuCustomMedicine::class, 'nicu_custom_medicine_id');
    }
}
