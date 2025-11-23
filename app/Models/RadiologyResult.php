<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RadiologyResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'radiology_request_id',
        'radiologist_id',
        'findings',
        'impression',
        'recommendations',
        'images',
        'is_preliminary',
        'reported_at'
    ];

    protected $casts = [
        'images' => 'array',
        'is_preliminary' => 'boolean',
        'reported_at' => 'datetime'
    ];

    // العلاقات
    public function request()
    {
        return $this->belongsTo(RadiologyRequest::class, 'radiology_request_id');
    }

    public function radiologist()
    {
        return $this->belongsTo(User::class, 'radiologist_id');
    }

    // النطاقات
    public function scopePreliminary($query)
    {
        return $query->where('is_preliminary', true);
    }

    public function scopeFinal($query)
    {
        return $query->where('is_preliminary', false);
    }

    // الخصائص المساعدة
    public function getStatusTextAttribute()
    {
        return $this->is_preliminary ? 'تقرير أولي' : 'تقرير نهائي';
    }

    // الدوال المساعدة
    public function markAsFinal()
    {
        $this->update([
            'is_preliminary' => false,
            'reported_at' => now()
        ]);
    }

    public function addImage($path)
    {
        $images = $this->images ?? [];
        $images[] = $path;
        $this->update(['images' => $images]);
    }
}
