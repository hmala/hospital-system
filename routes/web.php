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
use App\Http\Controllers\RadiologyController;
use App\Http\Controllers\RadiologyTypeController;
use App\Http\Controllers\SurgeryController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

// Ensure numeric IDs for {radiology} so '/radiology/types' isn't captured
Route::pattern('radiology', '[0-9]+');

Route::get('/', function () {
    return Auth::check() ? redirect('/dashboard') : redirect('/login');
});

// (Removed) Temporary local-only admin reset route

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
    Route::post('/visits/{visit}/request-surgery', [VisitController::class, 'requestSurgery'])
         ->name('visits.request-surgery');
         
    // مسارات الطبيب للزيارات
    Route::prefix('doctor')->name('doctor.')->group(function () {
        Route::get('/visits', [DoctorVisitController::class, 'index'])->name('visits.index');
        Route::get('/visits/{visit}', [DoctorVisitController::class, 'show'])->name('visits.show');
        Route::get('/visits/{visit}/surgery-form', [DoctorVisitController::class, 'showSurgeryForm'])->name('visits.show-surgery-form');
        Route::put('/visits/{visit}', [DoctorVisitController::class, 'update'])->name('visits.update');
        Route::put('/visits/{visit}/mark-needs-surgery', [DoctorVisitController::class, 'markNeedsSurgery'])->name('visits.mark-needs-surgery');
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
        Route::get('/requests/{request}/print', [StaffRequestController::class, 'print'])->name('requests.print');
        
        // زيارات المختبر
        Route::get('/lab-visits/create', [StaffRequestController::class, 'createLabVisit'])->name('lab-visits.create');
        Route::post('/lab-visits', [StaffRequestController::class, 'storeLabVisit'])->name('lab-visits.store');
        
        // طلبات المختبر للعمليات
        Route::get('/surgery-lab-tests', [StaffRequestController::class, 'surgeryLabTests'])->name('surgery-lab-tests.index');
        Route::get('/surgery-lab-tests/{test}', [StaffRequestController::class, 'showSurgeryLabTest'])->name('surgery-lab-tests.show');
        Route::put('/surgery-lab-tests/{test}', [StaffRequestController::class, 'updateSurgeryLabTest'])->name('surgery-lab-tests.update');
        
        // طلبات الأشعة للعمليات
        Route::get('/surgery-radiology-tests', [StaffRequestController::class, 'surgeryRadiologyTests'])->name('surgery-radiology-tests.index');
        Route::get('/surgery-radiology-tests/{test}', [StaffRequestController::class, 'showSurgeryRadiologyTest'])->name('surgery-radiology-tests.show');
        Route::put('/surgery-radiology-tests/{test}', [StaffRequestController::class, 'updateSurgeryRadiologyTest'])->name('surgery-radiology-tests.update');
        
        // التحويلات
        Route::resource('referrals', ReferralController::class);
    });

    // إدارة رموز ICD10
    Route::resource('icd10', \App\Http\Controllers\ICD10Controller::class);

    // إدارة الإشعة
    Route::prefix('radiology')->name('radiology.')->group(function () {
        Route::get('/', [RadiologyController::class, 'index'])->name('index');
        Route::get('/create', [RadiologyController::class, 'create'])->name('create');
        Route::post('/', [RadiologyController::class, 'store'])->name('store');
        Route::get('/{radiology}', [RadiologyController::class, 'show'])->name('show');
        Route::get('/{radiology}/print', [RadiologyController::class, 'print'])->name('print');
        Route::get('/{radiology}/edit', [RadiologyController::class, 'edit'])->name('edit');
        Route::put('/{radiology}', [RadiologyController::class, 'update'])->name('update');
        Route::delete('/{radiology}', [RadiologyController::class, 'destroy'])->name('destroy');

        // إجراءات إضافية
        Route::post('/{radiology}/schedule', [RadiologyController::class, 'schedule'])->name('schedule');
        Route::post('/{radiology}/start', [RadiologyController::class, 'startProcedure'])->name('start');
        Route::post('/{radiology}/complete', [RadiologyController::class, 'complete'])->name('complete');
        Route::post('/{radiology}/cancel', [RadiologyController::class, 'cancel'])->name('cancel');
        Route::post('/{radiology}/results', [RadiologyController::class, 'saveResults'])->name('saveResults');

        // إدارة أنواع الإشعة (للإداريين فقط)
        Route::prefix('types')->name('types.')->group(function () {
            Route::get('/', [RadiologyTypeController::class, 'index'])->name('index');
            Route::get('/create', [RadiologyTypeController::class, 'create'])->name('create');
            Route::post('/', [RadiologyTypeController::class, 'store'])->name('store');
            Route::get('/{type}', [RadiologyTypeController::class, 'show'])->name('show');
            Route::get('/{type}/edit', [RadiologyTypeController::class, 'edit'])->name('edit');
            Route::put('/{type}', [RadiologyTypeController::class, 'update'])->name('update');
            Route::delete('/{type}', [RadiologyTypeController::class, 'destroy'])->name('destroy');
            Route::post('/{type}/toggle', [RadiologyTypeController::class, 'toggleStatus'])->name('toggle');
        });
    });

    // إدارة العمليات
    Route::get('/surgeries/waiting', [SurgeryController::class, 'waitingList'])->name('surgeries.waiting');
    Route::get('/surgeries/control', [SurgeryController::class, 'controlPanel'])->name('surgeries.control');
    Route::post('/surgeries/{surgery}/check-in', [SurgeryController::class, 'checkIn'])->name('surgeries.check-in');
    Route::resource('surgeries', SurgeryController::class);
    Route::post('/surgeries/{surgery}/start', [SurgeryController::class, 'start'])->name('surgeries.start');
    Route::post('/surgeries/{surgery}/complete', [SurgeryController::class, 'complete'])->name('surgeries.complete');
    Route::post('/surgeries/{surgery}/cancel', [SurgeryController::class, 'cancel'])->name('surgeries.cancel');
    Route::post('/surgeries/{surgery}/return-to-waiting', [SurgeryController::class, 'returnToWaiting'])->name('surgeries.return-to-waiting');
});
