<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserLabTestGroup extends Model
{
    use HasFactory;

    protected $table = 'user_lab_test_groups';

    protected $fillable = [
        'user_id',
        'name',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function labTests(): BelongsToMany
    {
        return $this->belongsToMany(LabTest::class, 'user_lab_test_group_lab_test', 'group_id', 'lab_test_id');
    }
}
