<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class NicuCareSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'reservation_id',
        'type',
        'title',
        'dose_or_amount',
        'route',
        'interval_hours',
        'start_time',
        'next_due_at',
        'last_administered_at',
        'instructions',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'interval_hours' => 'integer',
        'start_time' => 'datetime',
        'next_due_at' => 'datetime',
        'last_administered_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(IncubatorReservation::class, 'reservation_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * هل التوصية مستحقة الآن أو متأخرة؟
     */
    public function getIsDueAttribute(): bool
    {
        if (!$this->is_active || !$this->next_due_at) {
            return false;
        }

        return $this->next_due_at->lte(now());
    }

    /**
     * هل التوصية متأخرة جداً (أكثر من 30 دقيقة)؟
     */
    public function getIsOverdueAttribute(): bool
    {
        if (!$this->is_active || !$this->next_due_at) {
            return false;
        }

        return $this->next_due_at->lte(now()->subMinutes(30));
    }

    /**
     * نص الوقت المتبقي أو التأخير بالعربية
     */
    public function getDueHumanAttribute(): string
    {
        if (!$this->next_due_at) {
            return 'غير محدد';
        }

        $now = now();
        if ($this->next_due_at->lte($now)) {
            $diffMins = (int) $this->next_due_at->diffInMinutes($now);
            if ($diffMins <= 5) {
                return 'مستحق الآن';
            }
            if ($diffMins < 60) {
                return 'متأخر منذ ' . $diffMins . ' دقيقة';
            }
            $diffHours = (int) $this->next_due_at->diffInHours($now);
            return 'متأخر منذ ' . $diffHours . ' ساعة';
        }

        $diffMins = (int) $now->diffInMinutes($this->next_due_at);
        if ($diffMins < 60) {
            return 'بعد ' . $diffMins . ' دقيقة';
        }
        $diffHours = (int) $now->diffInHours($this->next_due_at);
        return 'بعد ' . $diffHours . ' ساعة';
    }

    /**
     * اسم نوع التوصية بالعربية
     */
    public function getTypeArabicAttribute(): string
    {
        return match($this->type) {
            'feeding' => 'جدول الرضاعة والتغذية',
            'medication' => 'جدول إعطاء العلاج الدوائي',
            default => 'توصية سريرية'
        };
    }

    /**
     * أيقونة التوصية
     */
    public function getIconAttribute(): string
    {
        return match($this->type) {
            'feeding' => 'fas fa-baby-bottle',
            'medication' => 'fas fa-pills',
            default => 'fas fa-clipboard-check'
        };
    }
}
