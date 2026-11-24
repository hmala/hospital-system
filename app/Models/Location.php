<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * مخازن المستودع والردهات الصالحة لشاشات المخزون (باستثناء الصيدلية والأقسام الإدارية غير المخزنية إلا إذا احتوت أرصدة).
     */
    public function scopeWarehouseAccessible(Builder $query): Builder
    {
        $excludedKeywords = [
            'صيدل',
            'حسابات',
            'صيانة',
            'فندقة',
            'احصاء',
            'رسبشن',
            'ادارة',
            'استشار',
            'اسنان',
            'عيون',
        ];

        return $query->whereIn('type', ['main', 'sub'])
            ->where(function (Builder $outer) use ($excludedKeywords) {
                $outer->whereHas('stockBatches')
                    ->orWhere(function (Builder $inner) use ($excludedKeywords) {
                        foreach ($excludedKeywords as $keyword) {
                            $inner->where('name', 'not like', "%{$keyword}%");
                        }
                    });
            });
    }

    /**
     * استبعاد مخزن الصيدلة غير المستخدم من قوائم المواقع (الصيدلية مستقلة بجدول الأدوية).
     */
    public function scopeExcludeUnusedPharmacy(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->where('name', 'not like', '%صيدل%')
                ->orWhereHas('stockBatches');
        });
    }

    /**
     * استبعاد الصيدلة دائماً من قوائم النقل والطلبات الفرعية للمستودع العام.
     */
    public function scopeExcludePharmacy(Builder $query): Builder
    {
        return $query->where('name', 'not like', '%صيدل%');
    }
}
