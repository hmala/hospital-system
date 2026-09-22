<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicineBatch extends Model
{
    use HasFactory;

    protected $table = 'medicine_batches';

    protected $fillable = [
        'medicine_id',
        'batch_number',
        'expiry_date',
        'initial_quantity',
        'current_quantity',
        'current_sub_units',
        'purchase_price',
        'supplier_name',
        'received_at',
        'status',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'received_at' => 'date',
        'initial_quantity' => 'integer',
        'current_quantity' => 'integer',
        'current_sub_units' => 'integer',
        'purchase_price' => 'float',
    ];

    public function medicine()
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }

    public function saleItems()
    {
        return $this->hasMany(PharmacySaleItem::class, 'batch_id');
    }

    /**
     * ترتيب الوجبات تصاعدياً حسب تاريخ الانتهاء (FEFO)
     */
    public function scopeFefoOrder($query)
    {
        return $query->orderBy('expiry_date', 'asc');
    }

    /**
     * الوجبات النشطة والصالحة
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                     ->where('expiry_date', '>=', now()->toDateString());
    }

    /**
     * الوجبات القريبة من الانتهاء خلال فترة معينة (افتراضياً 90 يوماً)
     */
    public function scopeExpiringSoon($query, int $days = 90)
    {
        return $query->where('status', 'active')
                     ->whereBetween('expiry_date', [
                         now()->toDateString(),
                         now()->addDays($days)->toDateString(),
                     ])
                     ->orderBy('expiry_date', 'asc');
    }

    /**
     * الوجبات المنتهية الصلاحية
     */
    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now()->toDateString())
                     ->orWhere('status', 'expired');
    }

    /**
     * عدد الأيام المتبقية حتى انتهاء الصلاحية
     */
    public function getDaysUntilExpiryAttribute(): int
    {
        if (!$this->expiry_date) {
            return 0;
        }

        return (int) now()->startOfDay()->diffInDays($this->expiry_date->startOfDay(), false);
    }

    /**
     * هل الوجبة منتهية الصلاحية؟
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->days_until_expiry < 0;
    }

    /**
     * هل الوجبة قريبة الانتهاء (أقل من 90 يوم)؟
     */
    public function getIsExpiringSoonAttribute(): bool
    {
        return !$this->is_expired && $this->days_until_expiry <= 90;
    }

    /**
     * خصم الكمية من الوجبة مع دعم فتح العلب تلقائياً للأشرطة
     */
    public function deductStock(int $mainUnits = 0, int $subUnits = 0, int $subUnitsCount = 1): bool
    {
        $subUnitsCount = max(1, $subUnitsCount);

        // أولاً: خصم العلب الكاملة
        if ($mainUnits > 0) {
            if ($this->current_quantity < $mainUnits) {
                return false;
            }
            $this->current_quantity -= $mainUnits;
        }

        // ثانياً: خصم الأشرطة / الوحدات الصغرى
        if ($subUnits > 0) {
            if ($this->current_sub_units >= $subUnits) {
                // الأشرطة المتوفرة من علب مفتوحة مسبقاً تكفي
                $this->current_sub_units -= $subUnits;
            } else {
                // نحتاج فتح علبة كاملة أو أكثر
                $neededSubUnits = $subUnits - $this->current_sub_units;
                $boxesToOpen    = (int) ceil($neededSubUnits / $subUnitsCount);

                if ($this->current_quantity < $boxesToOpen) {
                    return false; // لا يوجد رصيد كافٍ
                }

                $this->current_quantity -= $boxesToOpen;
                $this->current_sub_units = ($this->current_sub_units + ($boxesToOpen * $subUnitsCount)) - $subUnits;
            }
        }

        // إذا نفدت العلب والأشرطة، تحديث الحالة إلى depleted
        if ($this->current_quantity === 0 && $this->current_sub_units === 0) {
            $this->status = 'depleted';
        }

        $this->save();
        return true;
    }
}
