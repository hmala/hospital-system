<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserMedicineGroup extends Model
{
    use HasFactory;

    protected $table = 'user_medicine_groups';

    protected $fillable = [
        'user_id',
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
            ->withPivot(['dosage_form', 'dosage', 'frequency', 'duration', 'instructions']);
    }
}
