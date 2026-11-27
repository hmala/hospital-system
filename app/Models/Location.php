<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Location extends Model
{
    use HasFactory;

    public const CODE_MAIN_WAREHOUSE = 'main';

    public const CODE_NICU_STORE = 'nicu';

    protected $fillable = [
        'name',
        'type',
        'code',
        'custodian_id',
        'cost_center_id',
    ];

    public function costCenter()
    {
        return $this->belongsTo(\App\Models\Accounting\CostCenter::class, 'cost_center_id');
    }

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

    /**
     * تحديد مخزن الخدج الفعّال للمستخدم:
     * يأخذ أولاً المخزن الفرعي المرتبط بالمستخدم (location_id من شاشة تعديل المستخدم)،
     * وإذا لم يكن مرتبطاً بمخزن فرعي يرجع إلى مخزن الخدج الافتراضي resolveNicuStore().
     */
    public static function resolveNicuStoreForUser(?User $user = null, bool $createIfMissing = true): ?self
    {
        if ($user && $user->location_id) {
            $userSubLocation = static::where('type', 'sub')->find($user->location_id);
            if ($userSubLocation) {
                return $userSubLocation;
            }
        }

        return static::resolveNicuStore($createIfMissing);
    }

    /**
     * تحديد مخزن وعُهدة قسم الخدج (NICU) ديناميكياً بدون أي اعتماد على ID ثابت.
     * الأولوية: إعداد nicu_settings -> عمود code='nicu' -> الاكتشاف بالاسم -> الإنشاء التلقائي.
     */
    public static function resolveNicuStore(bool $createIfMissing = true): ?self
    {
        $hasSettingsTable = Schema::hasTable('nicu_settings');
        $hasCodeColumn = Schema::hasColumn('locations', 'code');

        if ($hasSettingsTable) {
            $configuredId = NicuSetting::getInt('nicu_store_location_id', 0);
            if ($configuredId > 0) {
                $configured = static::where('type', 'sub')->find($configuredId);
                if ($configured) {
                    return $configured;
                }
            }
        }

        if ($hasCodeColumn) {
            $byCode = static::where('code', self::CODE_NICU_STORE)
                ->where('type', 'sub')
                ->first();

            if ($byCode) {
                if ($hasSettingsTable) {
                    NicuSetting::setValue(
                        'nicu_store_location_id',
                        (string) $byCode->id,
                        'مخزن وعُهدة قسم الخدج المعتمد'
                    );
                }

                return $byCode;
            }
        }

        $detected = static::where('type', 'sub')
            ->where(function (Builder $q) {
                $q->where('name', 'like', '%خدج%')
                    ->orWhere('name', 'like', '%حاضن%')
                    ->orWhere('name', 'like', '%NICU%');
            })
            ->orderBy('id')
            ->first();

        if ($detected) {
            if ($hasCodeColumn && empty($detected->code)) {
                $detected->update(['code' => self::CODE_NICU_STORE]);
            }

            if ($hasSettingsTable) {
                NicuSetting::setValue(
                    'nicu_store_location_id',
                    (string) $detected->id,
                    'مخزن وعُهدة قسم الخدج المعتمد'
                );
            }

            return $detected;
        }

        if (! $createIfMissing) {
            return null;
        }

        $payload = [
            'name' => 'مخزن الخدج والحاضنات',
            'type' => 'sub',
        ];

        if ($hasCodeColumn) {
            $payload['code'] = self::CODE_NICU_STORE;
        }

        $created = static::create($payload);

        if ($hasSettingsTable) {
            NicuSetting::setValue(
                'nicu_store_location_id',
                (string) $created->id,
                'مخزن وعُهدة قسم الخدج المعتمد'
            );
        }

        return $created;
    }

    /**
     * ربط وتثبيت موقع فرعي معين ليكون هو مخزن وعُهدة الخدج المعتمد في قاعدة البيانات الحالية.
     */
    public static function setNicuStore(self $location): self
    {
        if (Schema::hasColumn('locations', 'code')) {
            static::where('code', self::CODE_NICU_STORE)
                ->where('id', '!=', $location->id)
                ->update(['code' => null]);

            $location->update(['code' => self::CODE_NICU_STORE]);
        }

        if (Schema::hasTable('nicu_settings')) {
            NicuSetting::setValue(
                'nicu_store_location_id',
                (string) $location->id,
                'مخزن وعُهدة قسم الخدج المعتمد',
                'معرف الموقع المخزني الفرعي المعتمد لقسم الخدج والحاضنات'
            );
        }

        return $location->fresh();
    }
}
