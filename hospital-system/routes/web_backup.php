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

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // إدارة العيادات
    Route::resource('departments', DepartmentController::class);
    
    // إدارة الأطباء
    Route::resource('doctors', DoctorController::class);
    
    // إدارة المرضى
    Route::resource('patients', PatientController::class);
    Route::get('/patients/search', [PatientController::class, 'search'])->name('patients.search');
    
    // إدارة المواعيد
    Route::resource('appointments', AppointmentController::class);
    Route::post('/appointments/{appointment}/confirm', [AppointmentController::class, 'confirm'])->name('appointments.confirm');
    Route::post('/appointments/{appointment}/complete', [AppointmentController::class, 'complete'])->name('appointments.complete');
    Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::get('/appointments/available-slots', [AppointmentController::class, 'getAvailableSlots'])->name('appointments.available-slots');
    
    // مسارات الاستعلامات - يجب أن تأتي قبل resource
    Route::get('/inquiry/search', [InquiryController::class, 'search'])->name('inquiry.search');
    Route::get('/inquiry/search/patients', [InquiryController::class, 'searchPatients'])->name('inquiry.search.patients');
    Route::resource('inquiry', InquiryController::class);
    
    // مسارات الزيارات
    Route::resource('visits', VisitController::class);
    Route::get('/visits/create/{patient_id}/{appointment_id}', [VisitController::class, 'create'])
         ->name('visits.create.from_appointment');
    Route::get('/visits/create/{patient_id}', [VisitController::class, 'create'])
         ->name('visits.create.for_patient');
    Route::put('/appointments/{appointment}/convert', [DoctorVisitController::class, 'convertAppointmentToVisit'])
         ->name('appointments.convert');
         
    // مسارات الطبيب للزيارات
    Route::prefix('doctor')->name('doctor.')->group(function () {
        Route::get('/visits', [DoctorVisitController::class, 'index'])->name('visits.index');
        Route::get('/visits/{visit}', [DoctorVisitController::class, 'show'])->name('visits.show');
        Route::put('/visits/{visit}', [DoctorVisitController::class, 'update'])->name('visits.update');
        Route::delete('/visits/{visit}', [DoctorVisitController::class, 'cancel'])->name('visits.cancel');
        Route::post('/requests', [DoctorVisitController::class, 'storeRequest'])->name('requests.store');
        Route::put('/requests/{request}', [DoctorVisitController::class, 'updateRequestStatus'])->name('requests.update');
        Route::put('/requests/{request}/status', [DoctorVisitController::class, 'updateRequestStatus'])->name('requests.update_status');
    });
    
    // مسارات المريض للزيارات
    Route::prefix('patient')->name('patient.')->group(function () {
        Route::get('/visits', [PatientVisitController::class, 'index'])->name('visits.index');
        Route::get('/visits/{visit}', [PatientVisitController::class, 'show'])->name('visits.show');
    });
    
    // مسارات الموظفين الطبيين
    Route::prefix('staff')->name('staff.')->group(function () {
        // الطلبات
        Route::get('/requests/{type?}', [StaffRequestController::class, 'index'])->name('requests.index');
        Route::get('/requests/{request}/show', [StaffRequestController::class, 'show'])->name('requests.show');
        Route::put('/requests/{request}', [StaffRequestController::class, 'update'])->name('requests.update');
        
        // زيارات المختبر
        Route::get('/lab-visits/create', [StaffRequestController::class, 'createLabVisit'])->name('lab-visits.create');
        Route::post('/lab-visits', [StaffRequestController::class, 'storeLabVisit'])->name('lab-visits.store');
        
        // التحويلات
        Route::resource('referrals', ReferralController::class);
    });

    // إدارة رموز ICD10
    Route::resource('icd10', \App\Http\Controllers\ICD10Controller::class);
});
