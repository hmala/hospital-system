<?php
// app/Models/Patient.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'emergency_contact', 'blood_type', 'medical_history', 'allergies',
        'current_medications', 'insurance_company', 'insurance_number',
        'national_id', 'first_visit_date', 'notes', 'mother_name', 'country_id',
        'governorate', 'district', 'neighborhood', 'marital_status', 'covered_by_insurance', 'insurance_booklet_number'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'first_visit_date' => 'date'
    ];

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    // العلاقة الجديدة مع الزيارات
    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function getAgeAttribute()
    {
        return $this->user && $this->user->date_of_birth ? 
            \Carbon\Carbon::parse($this->user->date_of_birth)->age : null;
    }

    // دالة للحصول على آخر زيارة
    public function getLastVisit()
    {
        return $this->visits()->latest()->first();
    }

    // دالة للحصول على تاريخ آخر زيارة
    public function getLastVisitDate()
    {
        $lastVisit = $this->getLastVisit();
        return $lastVisit ? $lastVisit->visit_date : null;
    }

    // دالة للحصول على عدد الزيارات
    public function getVisitsCount()
    {
        return $this->visits()->count();
    }

    // دالة للحصول على عدد المواعيد
    public function getAppointmentsCount()
    {
        return $this->appointments()->count();
    }

    // دالة للحصول على الزيارات في شهر معين
    public function getVisitsThisMonth()
    {
        return $this->visits()
            ->whereYear('visit_date', now()->year)
            ->whereMonth('visit_date', now()->month)
            ->count();
    }
}