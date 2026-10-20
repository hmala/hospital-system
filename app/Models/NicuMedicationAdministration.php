<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NicuMedicationAdministration extends Model
{
    use HasFactory;

    protected $table = 'nicu_medication_administrations';

    protected $fillable = [
        'reservation_id',
        'medication_name',
        'route',
        'dose',
        'given_at',
        'nurse_id',
        'notes',
    ];

    protected $casts = [
        'given_at' => 'datetime',
    ];

    // أنواع وطرق الإعطاء
    const ROUTE_IV = 'iv';
    const ROUTE_INFUSION = 'infusion';
    const ROUTE_IM = 'im';
    const ROUTE_ORAL = 'oral';
    const ROUTE_NEBULIZER = 'nebulizer';
    const ROUTE_DROPS = 'drops';
    const ROUTE_SC = 'sc';
    const ROUTE_PR = 'pr';
    const ROUTE_TOPICAL = 'topical';

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(IncubatorReservation::class, 'reservation_id');
    }

    public function nurse(): BelongsTo
    {
        return $this->belongsTo(User::class, 'nurse_id');
    }

    public function getRouteNameAttribute(): string
    {
        return match($this->route) {
            'iv' => 'حقن وريدي (IV)',
            'infusion' => 'تسريب وريدي مستمر (IV Infusion)',
            'im' => 'عضلي (IM)',
            'oral' => 'فموي / أنبوب تغذية (Oral / NG)',
            'nebulizer' => 'استنشاق / رذاذ (Nebulizer)',
            'drops' => 'قطرات (Drops)',
            'sc' => 'تحت الجلد (SC)',
            'pr' => 'شرجي (PR)',
            'topical' => 'موضعي (Topical)',
            default => $this->route ?? 'أخرى',
        };
    }

    public function getRouteBadgeColorAttribute(): string
    {
        return match($this->route) {
            'iv' => 'danger',
            'infusion' => 'primary',
            'im' => 'warning text-dark',
            'oral' => 'success',
            'nebulizer' => 'info text-white',
            'drops' => 'secondary',
            default => 'dark',
        };
    }
}
