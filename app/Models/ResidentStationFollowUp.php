<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResidentStationFollowUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'surgery_id',
        'resident_station_id',
        'resident_id',
        'resident_name',
        'follow_up_date',
        'session',
        'clinical_signs',
        'other_notes',
        'notes',
    ];

    protected $casts = [
        'follow_up_date' => 'date',
        'clinical_signs' => 'array',
    ];

    public static function availableClinicalSigns(): array
    {
        return [
            'oriented' => [
                'key' => 'oriented',
                'en' => 'Oriented',
                'ar' => 'واعي ومتجاوب',
                'badge' => 'success',
                'icon' => 'fa-check-circle',
            ],
            'clear_chest' => [
                'key' => 'clear_chest',
                'en' => 'Clear chest',
                'ar' => 'الصدر سليم',
                'badge' => 'info',
                'icon' => 'fa-lungs',
            ],
            'soft_abdomen' => [
                'key' => 'soft_abdomen',
                'en' => 'Soft abdomen',
                'ar' => 'البطن رخو',
                'badge' => 'primary',
                'icon' => 'fa-hand-holding-medical',
            ],
            'urine_output' => [
                'key' => 'urine_output',
                'en' => 'Urine output',
                'ar' => 'إدرار البول طبيعي',
                'badge' => 'teal',
                'icon' => 'fa-tint',
            ],
            'yellowish_discolouration' => [
                'key' => 'yellowish_discolouration',
                'en' => 'Yellowish discolouration',
                'ar' => 'اصفرار / يرقان',
                'badge' => 'warning',
                'icon' => 'fa-exclamation-triangle',
            ],
        ];
    }

    /**
     * Get evaluated positive signs keys.
     *
     * @return array<int, string>
     */
    public function getPositiveSignsAttribute(): array
    {
        $signs = $this->clinical_signs;
        if (empty($signs)) {
            return [];
        }

        if (isset($signs['positive']) && is_array($signs['positive'])) {
            return $signs['positive'];
        }

        if (array_is_list($signs)) {
            return $signs;
        }

        $positives = [];
        foreach ($signs as $key => $val) {
            if ($val === 'positive' || $val === true || $val === 1 || $val === '1') {
                $positives[] = $key;
            }
        }

        return $positives;
    }

    /**
     * Get evaluated negative signs keys.
     *
     * @return array<int, string>
     */
    public function getNegativeSignsAttribute(): array
    {
        $signs = $this->clinical_signs;
        if (empty($signs)) {
            return [];
        }

        if (isset($signs['negative']) && is_array($signs['negative'])) {
            return $signs['negative'];
        }

        $allKeys = array_keys(static::availableClinicalSigns());
        $positive = $this->positive_signs;

        return array_values(array_diff($allKeys, $positive));
    }

    public function surgery()
    {
        return $this->belongsTo(Surgery::class);
    }

    public function residentStation()
    {
        return $this->belongsTo(ResidentStation::class, 'resident_station_id');
    }

    public function resident()
    {
        return $this->belongsTo(Doctor::class, 'resident_id');
    }
}
