<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserMedicineGroup extends Model
{
    use HasFactory;

    protected $table = 'user_medicine_groups';

    protected $fillable = [
        'user_id',
        'department',
        'name',
        'description',
        'is_public',
        'is_starred',
        'usage_count',
        'sort_order',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_starred' => 'boolean',
        'usage_count' => 'integer',
        'sort_order' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function medicines(): BelongsToMany
    {
        return $this->belongsToMany(Medicine::class, 'user_medicine_group_medicine', 'group_id', 'medicine_id')
            ->withPivot(['dosage_form', 'dosage', 'route', 'frequency', 'duration', 'duration_days', 'instructions']);
    }

    public function customItems(): HasMany
    {
        return $this->hasMany(UserMedicineGroupCustomItem::class, 'group_id');
    }

    /**
     * تنسيق موحد لجميع أدوية الباقة (من الصيدلية + المضافة بالخدج) للاستخدام في الواجهة.
     */
    public function getFormattedMedicationsAttribute(): \Illuminate\Support\Collection
    {
        $standard = $this->medicines->map(fn ($m) => [
            'medicine_id' => $m->id,
            'name' => $m->name,
            'type' => $m->pivot->dosage_form,
            'dosage' => $m->pivot->dosage,
            'route' => $m->pivot->route,
            'frequency' => $m->pivot->frequency,
            'duration' => $m->pivot->duration,
            'duration_days' => $m->pivot->duration_days,
            'instructions' => $m->pivot->instructions,
            'is_custom' => false,
        ]);

        $custom = $this->customItems->map(fn ($c) => [
            'medicine_id' => null,
            'nicu_custom_medicine_id' => $c->nicu_custom_medicine_id,
            'name' => $c->medicine_name,
            'type' => $c->dosage_form,
            'dosage' => $c->dosage,
            'route' => $c->route,
            'frequency' => $c->frequency,
            'duration' => $c->duration,
            'duration_days' => $c->duration_days,
            'instructions' => $c->instructions,
            'is_custom' => true,
        ]);

        return $standard->concat($custom)->values();
    }
}
