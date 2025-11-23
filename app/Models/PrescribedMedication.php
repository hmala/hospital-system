<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescribedMedication extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'item_type',
        'name',
        'type',
        'dosage',
        'frequency',
        'times',
        'duration',
        'instructions',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function getTypeTextAttribute()
    {
        $types = [
            'tablet' => 'حبوب',
            'injection' => 'إبرة',
            'syrup' => 'شراب',
            'cream' => 'كريم',
            'drops' => 'قطرات',
            'physical_therapy' => 'علاج فيزيائي',
            'occupational_therapy' => 'علاج وظيفي',
            'speech_therapy' => 'علاج نطقي',
            'surgery' => 'جراحة',
            'radiotherapy' => 'علاج إشعاعي',
            'chemotherapy' => 'علاج كيميائي',
            'other' => 'أخرى'
        ];
        return $types[$this->type] ?? $this->type;
    }

    public function getItemTypeTextAttribute()
    {
        return $this->item_type === 'medication' ? 'دواء' : 'علاج';
    }
}
