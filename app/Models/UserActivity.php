<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class UserActivity extends Model
{
    use HasFactory;

    protected $table = 'user_activities';

    protected $fillable = [
        'user_id',
        'user_name',
        'role_name',
        'action',
        'category',
        'description',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'user_agent',
        'is_sensitive',
    ];

    protected $casts = [
        'properties' => 'array',
        'is_sensitive' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeCategory($query, $category)
    {
        if ($category && $category !== 'all') {
            return $query->where('category', $category);
        }
        return $query;
    }

    public function scopeForUser($query, $userId)
    {
        if ($userId && $userId !== 'all') {
            return $query->where('user_id', $userId);
        }
        return $query;
    }

    public function scopeSensitiveOnly($query)
    {
        return $query->where('is_sensitive', true);
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'auth' => 'المصادقة والأمان',
            'reception' => 'الاستقبال والحجوزات',
            'cashier' => 'الصندوق والمالية',
            'doctor' => 'العيادات والأطباء',
            'lab' => 'المختبر والتحاليل',
            'radiology' => 'الأشعة والتصوير',
            'pharmacy' => 'الصيدلية والعلاج',
            'treasury' => 'الخزينة والمصروفات',
            'surgery' => 'العمليات والرقود',
            'emergency' => 'الطوارئ والإسعاف',
            'security' => 'الرقابة والأمان',
            default => 'عام',
        };
    }

    public function getCategoryColorAttribute(): string
    {
        if ($this->action === 'logout') {
            return 'secondary';
        }
        if ($this->action === 'login') {
            return 'success';
        }

        return match ($this->category) {
            'auth' => 'secondary',
            'reception' => 'primary',
            'cashier' => 'success',
            'doctor' => 'info',
            'lab' => 'warning',
            'radiology' => 'teal',
            'pharmacy' => 'indigo',
            'treasury' => 'dark',
            'surgery' => 'danger',
            'emergency' => 'danger',
            'security' => 'danger',
            default => 'primary',
        };
    }

    public function getCategoryIconAttribute(): string
    {
        if ($this->action === 'logout') {
            return 'fas fa-sign-out-alt';
        }
        if ($this->action === 'login') {
            return 'fas fa-sign-in-alt';
        }

        return match ($this->category) {
            'auth' => 'fas fa-key',
            'reception' => 'fas fa-calendar-check',
            'cashier' => 'fas fa-cash-register',
            'doctor' => 'fas fa-stethoscope',
            'lab' => 'fas fa-vial',
            'radiology' => 'fas fa-x-ray',
            'pharmacy' => 'fas fa-pills',
            'treasury' => 'fas fa-vault',
            'surgery' => 'fas fa-procedures',
            'emergency' => 'fas fa-ambulance',
            'security' => 'fas fa-shield-alt',
            default => 'fas fa-history',
        };
    }
}
