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
use App\Http\Controllers\RadiologyController;
use App\Http\Controllers\RadiologyTypeController;
use App\Http\Controllers\SurgeryController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\RoleManagementController;
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

// Routes متاحة للجميع
Route::get('/departments', [DepartmentController::class, 'publicIndex'])
    ->name('departments.public');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // إدارة العيادات (استثناء index لأنه متاح للجميع)
    Route::get('/departments/admin', [DepartmentController::class, 'index'])
        ->name('departments.admin');
    Route::resource('departments', DepartmentController::class)->except(['index']);
    
    // إدارة الأطباء
    Route::resource('doctors', DoctorController::class);
    
    // إدارة المرضى
    Route::resource('patients', PatientController::class);
    Route::get('/patients/search', [PatientController::class, 'search'])->name('patients.search');
    
    // إدارة المستخدمين (للمشرف فقط)
    Route::resource('users', UserManagementController::class);
    
    // إدارة الأدوار والصلاحيات (للمشرف فقط)
    Route::prefix('roles')->name('roles.')->group(function () {
        Route::get('/', [RoleManagementController::class, 'rolesIndex'])->name('index');
        Route::get('/create', [RoleManagementController::class, 'rolesCreate'])->name('create');
        Route::post('/', [RoleManagementController::class, 'rolesStore'])->name('store');
        Route::get('/{role}/edit', [RoleManagementController::class, 'rolesEdit'])->name('edit');
        Route::put('/{role}', [RoleManagementController::class, 'rolesUpdate'])->name('update');
        Route::delete('/{role}', [RoleManagementController::class, 'rolesDestroy'])->name('destroy');
    });
    
    Route::prefix('permissions')->name('permissions.')->group(function () {
        Route::get('/', [RoleManagementController::class, 'permissionsIndex'])->name('index');
        Route::get('/create', [RoleManagementController::class, 'permissionsCreate'])->name('create');
        Route::post('/', [RoleManagementController::class, 'permissionsStore'])->name('store');
        Route::delete('/{permission}', [RoleManagementController::class, 'permissionsDestroy'])->name('destroy');
    });
    
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
    
    // مسارات الكاشير (Cashier Routes)
    Route::prefix('cashier')->name('cashier.')->group(function () {
        Route::get('/', [\App\Http\Controllers\CashierController::class, 'index'])->name('index');
        Route::get('/payment/{appointment}', [\App\Http\Controllers\CashierController::class, 'showPaymentForm'])->name('payment.form');
        Route::post('/payment/{appointment}', [\App\Http\Controllers\CashierController::class, 'processPayment'])->name('payment.process');
        Route::get('/receipt/{payment}', [\App\Http\Controllers\CashierController::class, 'showReceipt'])->name('receipt');
        Route::get('/receipt/{payment}/print', [\App\Http\Controllers\CashierController::class, 'printReceipt'])->name('receipt.print');
        Route::get('/report', [\App\Http\Controllers\CashierController::class, 'paymentsReport'])->name('report');
    });
    
    // مسارات الإشعارات (Notifications Routes)
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [\App\Http\Controllers\NotificationController::class, 'index'])->name('index');
        Route::post('/{notification}/mark-as-read', [\App\Http\Controllers\NotificationController::class, 'markAsRead'])->name('mark-as-read');
        Route::post('/mark-all-as-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead'])->name('mark-all-as-read');
        Route::delete('/{notification}', [\App\Http\Controllers\NotificationController::class, 'destroy'])->name('destroy');
        Route::get('/unread-count', [\App\Http\Controllers\NotificationController::class, 'getUnreadCount'])->name('unread-count');
    });
    
    // مسارات الزيارات
    Route::resource('visits', VisitController::class);
    Route::get('/visits/create/{patient_id}/{appointment_id}', [VisitController::class, 'create'])
         ->name('visits.create.from_appointment');
    Route::get('/visits/create/{patient_id}', [VisitController::class, 'create'])
         ->name('visits.create.for_patient');
    Route::post('/appointments/{appointment}/convert-to-visit', [VisitController::class, 'createFromAppointment'])
         ->name('visits.create-from-appointment');
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
        Route::put('/appointments/{appointment}/convert', [DoctorVisitController::class, 'convertAppointmentToVisit'])->name('appointments.convert');
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
        Route::get('/surgery-lab-tests/{test}/print', [StaffRequestController::class, 'printSurgeryLabTest'])->name('surgery-lab-tests.print');
        
        // طلبات الأشعة للعمليات
        Route::get('/surgery-radiology-tests', [StaffRequestController::class, 'surgeryRadiologyTests'])->name('surgery-radiology-tests.index');
        Route::get('/surgery-radiology-tests/{test}', [StaffRequestController::class, 'showSurgeryRadiologyTest'])->name('surgery-radiology-tests.show');
        Route::put('/surgery-radiology-tests/{test}', [StaffRequestController::class, 'updateSurgeryRadiologyTest'])->name('surgery-radiology-tests.update');
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

    // إدارة أنواع التحاليل المختبرية (للإداريين وموظفي المختبر)
    Route::prefix('lab-tests')->name('lab-tests.')->group(function () {
        Route::get('/', [\App\Http\Controllers\LabTestController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\LabTestController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\LabTestController::class, 'store'])->name('store');
        Route::get('/{labTest}', [\App\Http\Controllers\LabTestController::class, 'show'])->name('show');
        Route::get('/{labTest}/edit', [\App\Http\Controllers\LabTestController::class, 'edit'])->name('edit');
        Route::put('/{labTest}', [\App\Http\Controllers\LabTestController::class, 'update'])->name('update');
        Route::delete('/{labTest}', [\App\Http\Controllers\LabTestController::class, 'destroy'])->name('destroy');
        Route::post('/{labTest}/toggle-status', [\App\Http\Controllers\LabTestController::class, 'toggleStatus'])->name('toggle-status');
    });

    // إدارة العمليات
    Route::get('/surgeries/waiting', [SurgeryController::class, 'waitingList'])->name('surgeries.waiting');
    Route::get('/surgeries/control', [SurgeryController::class, 'controlPanel'])->name('surgeries.control');
    Route::post('/surgeries/{surgery}/check-in', [SurgeryController::class, 'checkIn'])->name('surgeries.check-in');
    Route::resource('surgeries', SurgeryController::class);
    Route::patch('/surgeries/{surgery}/update-details', [SurgeryController::class, 'updateDetails'])->name('surgeries.updateDetails');
    Route::post('/surgeries/{surgery}/start', [SurgeryController::class, 'start'])->name('surgeries.start');
    Route::post('/surgeries/{surgery}/complete', [SurgeryController::class, 'complete'])->name('surgeries.complete');
    Route::post('/surgeries/{surgery}/cancel', [SurgeryController::class, 'cancel'])->name('surgeries.cancel');
    Route::post('/surgeries/{surgery}/return-to-waiting', [SurgeryController::class, 'returnToWaiting'])->name('surgeries.return-to-waiting');
});
