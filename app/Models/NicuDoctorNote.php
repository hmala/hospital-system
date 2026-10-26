<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NicuDoctorNote extends Model
{
    use HasFactory;

    protected $table = 'nicu_doctor_notes';

    protected $fillable = [
        'incubator_reservation_id',
        'doctor_id',
        'doctor_name',
        'content',
        'is_urgent',
        'is_pinned',
    ];

    protected $casts = [
        'is_urgent' => 'boolean',
        'is_pinned' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function reservation()
    {
        return $this->belongsTo(IncubatorReservation::class, 'incubator_reservation_id');
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }
}
