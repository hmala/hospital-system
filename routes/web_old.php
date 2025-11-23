<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DoctorController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\VisitController;
use App\Http\Controllers\DoctorVisitController;
use App\Http\Controllers\PatientVisitController;
use App\Http\Controllers\StaffRequestController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\ReferralController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect('/dashboard') : redirect('/login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

require __DIR__.'/auth.php';
// routes/web.php - أضف هذه المسارات:

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // إدارة العيادات
    Route::resource('departments', DepartmentController::class);
});
// routes/web.php - أضف مسارات الأطباء:

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // إدارة العيادات
    Route::resource('departments', DepartmentController::class);
    
    // إدارة الأطباء
    Route::resource('doctors', DoctorController::class);
});
// routes/web.php - أضف مسارات المرضى:

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    Route::resource('departments', DepartmentController::class);
    Route::resource('doctors', DoctorController::class);
    Route::resource('patients', PatientController::class);
    
    // بحث المرضى
    Route::get('/patients/search', [PatientController::class, 'search'])->name('patients.search');
});
// routes/web.php - أضف مسارات المواعيد:

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    Route::resource('departments', DepartmentController::class);
    Route::resource('doctors', DoctorController::class);
    Route::resource('patients', PatientController::class);
    Route::resource('appointments', AppointmentController::class);
    
    // مسارات إضافية للمواعيد
    Route::post('/appointments/{appointment}/confirm', [AppointmentController::class, 'confirm'])->name('appointments.confirm');
    Route::post('/appointments/{appointment}/complete', [AppointmentController::class, 'complete'])->name('appointments.complete');
    Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::get('/appointments/available-slots', [AppointmentController::class, 'getAvailableSlots'])->name('appointments.available-slots');
    
    Route::get('/patients/search', [PatientController::class, 'search'])->name('patients.search');
});
// routes/web.php - إضافة مسارات الزيارات

// routes/web.php
Route::middleware(['auth'])->group(function () {
    // ... المسارات الحالية
    
    // مسارات الزيارات
    Route::resource('visits', VisitController::class);
    
    // مسار خاص لإنشاء زيارة من موعد
    Route::get('/visits/create/{patient_id}/{appointment_id}', [VisitController::class, 'create'])
         ->name('visits.create.from_appointment');
         
    Route::get('/visits/create/{patient_id}', [VisitController::class, 'create'])
         ->name('visits.create.for_patient');
         
    // مسار تحويل المواعيد إلى زيارات (متاح للأطباء وموظفي الاستقبال)
    Route::put('/appointments/{appointment}/convert', [DoctorVisitController::class, 'convertAppointmentToVisit'])->name('appointments.convert');
         
    // مسارات الطبيب للزيارات
    Route::prefix('doctor')->name('doctor.')->group(function () {
        Route::get('/visits', [DoctorVisitController::class, 'index'])->name('visits.index');
        Route::get('/visits/{visit}', [DoctorVisitController::class, 'show'])->name('visits.show');
        Route::put('/visits/{visit}', [DoctorVisitController::class, 'update'])->name('visits.update');
        Route::delete('/visits/{visit}', [DoctorVisitController::class, 'cancel'])->name('visits.cancel');
        Route::put('/requests/{request}', [DoctorVisitController::class, 'updateRequestStatus'])->name('requests.update_status');
        
        // مسارات الطلبات
        Route::post('/requests', [DoctorVisitController::class, 'storeRequest'])->name('requests.store');
        Route::put('/requests/{request}', [DoctorVisitController::class, 'updateRequestStatus'])->name('requests.update');
    });
    
    // مسارات المريض للزيارات
    Route::prefix('patient')->name('patient.')->group(function () {
        Route::get('/visits', [PatientVisitController::class, 'index'])->name('visits.index');
        Route::get('/visits/{visit}', [PatientVisitController::class, 'show'])->name('visits.show');
    });
    
    // مسارات الموظفين الطبيين للطلبات
    Route::prefix('staff')->name('staff.')->group(function () {
        Route::get('/requests/{type?}', [StaffRequestController::class, 'index'])->name('requests.index');
        Route::get('/requests/{request}/show', [StaffRequestController::class, 'show'])->name('requests.show');
        Route::put('/requests/{request}', [StaffRequestController::class, 'update'])->name('requests.update');
        
        // إنشاء زيارة مختبرية مباشرة
        Route::get('/lab-visits/create', [StaffRequestController::class, 'createLabVisit'])->name('lab-visits.create');
        Route::post('/lab-visits', [StaffRequestController::class, 'storeLabVisit'])->name('lab-visits.store');
    });

    // إدارة رموز ICD10
    Route::resource('icd10', \App\Http\Controllers\ICD10Controller::class);
    
    // مسارات الاستعلامات
    Route::resource('inquiry', InquiryController::class);
    Route::get('/inquiry/search', [InquiryController::class, 'search'])->name('inquiry.search');
    Route::get('/inquiry/search/patients', [InquiryController::class, 'searchPatients'])->name('inquiry.search.patients');
    
    // مسارات التحويلات
    Route::prefix('staff')->name('staff.')->group(function () {
        Route::resource('referrals', ReferralController::class);
    });
});