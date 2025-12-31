<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone',
        'specialization',
        'address',
        'date_of_birth',
        'gender',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'date_of_birth' => 'date',
        ];
    }

    // العلاقات
    public function patient()
    {
        return $this->hasOne(\App\Models\Patient::class);
    }

    public function doctor()
    {
        return $this->hasOne(\App\Models\Doctor::class);
    }

    public function visits()
    {
        return $this->hasMany(\App\Models\Visit::class, 'doctor_id');
    }

    // مساعدات الأدوار - متوافقة مع Spatie Permission
    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function isDoctor()
    {
        return $this->hasRole('doctor');
    }

    public function isPatient()
    {
        return $this->hasRole('patient');
    }

    public function isReceptionist()
    {
        return $this->hasRole('receptionist');
    }

    public function isStaff()
    {
        return $this->hasAnyRole(['lab_staff', 'radiology_staff', 'pharmacy_staff', 'surgery_staff']);
    }

    public function isSurgeryStaff()
    {
        return $this->hasRole('surgery_staff');
    }
}
