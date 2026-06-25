<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ConsultantAvailabilityController;
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
use App\Http\Controllers\UserLabTestGroupController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\RoleManagementController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Ensure numeric IDs for {radiology} so '/radiology/types' isn't captured
Route::pattern('radiology', '[0-9]+');

Route::get('/', function () {
    return Auth::check() ? redirect('/dashboard') : redirect('/login');
});

// Test route
Route::get('/test-cashier-data', function() {
    $pendingRequests = \App\Models\Request::with(['visit.patient.user', 'visit.doctor.user'])
        ->where('payment_status', 'pending')
        ->whereHas('visit', function($q) {
            $q->where('status', '!=', 'cancelled');
        })
        ->orderBy('created_at', 'desc')
        ->paginate(15, ['*'], 'requests_page');
    
    return view('test-cashier-view', compact('pendingRequests'));
})->middleware('auth');

// (Removed) Temporary local-only admin reset route

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

require __DIR__.'/auth.php';

// Routes متاحة للجميع
Route::get('/departments', [DepartmentController::class, 'publicIndex'])
    ->name('departments.public');

// API routes بدون أي middleware
Route::withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class, \Illuminate\Auth\Middleware\Authenticate::class])->group(function () {
    Route::post('/api/consultant-availability/bulk-update', [ConsultantAvailabilityController::class, 'bulkUpdate'])->name('api.consultant-availability.bulk-update');
});

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // إدارة العيادات (استثناء index لأنه متاح للجميع)
    Route::get('/departments/admin', [DepartmentController::class, 'index'])
        ->name('departments.admin');
    Route::resource('departments', DepartmentController::class)->except(['index']);
    
    // إدارة الأطباء
    Route::resource('doctors', DoctorController::class);
    Route::patch('/doctors/{doctor}/availability', [DoctorController::class, 'updateAvailability'])->name('doctors.update-availability');
    
    // إدارة توفر الأطباء الاستشاريين (لموظف الاستقبال)
    Route::get('/consultant-availability', [ConsultantAvailabilityController::class, 'index'])
        ->name('consultant-availability.index');
    Route::get('/consultant-availability/financial-movements', [ConsultantAvailabilityController::class, 'financialMovements'])
        ->name('consultant-availability.financial-movements');
    Route::get('/consultant-availability/financial-movements/export', [ConsultantAvailabilityController::class, 'exportFinancialMovements'])
        ->name('consultant-availability.financial-movements.export');
    Route::get('/consultant-availability/doctor-accounts', [ConsultantAvailabilityController::class, 'doctorAccounts'])
        ->name('consultant-availability.doctor-accounts');
    Route::get('/consultant-availability/doctor-accounts/{doctor}', [ConsultantAvailabilityController::class, 'doctorAccount'])
        ->name('consultant-availability.doctor-account');
    Route::get('/consultant-availability/doctor-accounts/{doctor}/export', [ConsultantAvailabilityController::class, 'exportDoctorAccount'])
        ->name('consultant-availability.doctor-account.export');
    Route::post('/consultant-availability/doctor-accounts/{doctor}/payout', [ConsultantAvailabilityController::class, 'doctorPayout'])
        ->name('consultant-availability.doctor-payout');
    Route::get('/debug-user', function () {
        return \App\Models\User::with(['roles','permissions'])->limit(10)->get();
    });
    Route::get('/consultant-availability/test', [ConsultantAvailabilityController::class, 'test'])->name('consultant-availability.test');
    Route::get('/consultant-availability/simple', [ConsultantAvailabilityController::class, 'simple'])->name('consultant-availability.simple');
    Route::get('/test-link', function () {
        return view('test_link');
    })->name('test-link');
    Route::get('/test-consultant-login', function () {
        $user = \App\Models\User::where('email', 'reception@hospital.com')->first();
        if ($user) {
            \Illuminate\Support\Facades\Auth::login($user);
            return redirect('/consultant-availability')->with('success', 'تم تسجيل الدخول بنجاح');
        }
        return 'User not found';
    })->name('test-consultant-login');
    Route::post('/test-login', function (\Illuminate\Http\Request $request) {
        $credentials = $request->only('email', 'password');
        if (\Illuminate\Support\Facades\Auth::attempt($credentials)) {
            return redirect('/dashboard');
        }
        return back()->withErrors(['email' => 'Invalid credentials']);
    })->name('test-login');
    Route::patch('/consultant-availability/{doctor}', [ConsultantAvailabilityController::class, 'updateAvailability'])->name('consultant-availability.update');
    Route::post('/consultant-availability/bulk-update', [ConsultantAvailabilityController::class, 'bulkUpdate'])->name('consultant-availability.bulk-update');
    
    // إدارة المرضى
    Route::resource('patients', PatientController::class);
    Route::get('/patients/search', [PatientController::class, 'search'])->name('patients.search');
    
    // إدارة المستخدمين (للمشرف فقط)
    Route::resource('users', UserManagementController::class);
    Route::patch('/users/{user}/toggle-status', [UserManagementController::class, 'toggleStatus'])->name('users.toggle-status');

    // روابط القائمة الجانبية يمكن لمسؤولي النظام تعديل رؤية كل رابط
    Route::resource('sidebar-links', \App\Http\Controllers\SidebarLinkController::class);
    
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

    // حجز رقود مبدئي مستقل
    Route::resource('bed-reservations', \App\Http\Controllers\BedReservationController::class)->only(['index','create','store']);
    // علامة دخول المريض للغرفة
    Route::post('bed-reservations/{reservation}/confirm', [\App\Http\Controllers\BedReservationController::class, 'confirm'])
        ->name('bed-reservations.confirm');
    Route::post('/appointments/{appointment}/confirm', [AppointmentController::class, 'confirm'])->name('appointments.confirm');
    Route::post('/appointments/{appointment}/complete', [AppointmentController::class, 'complete'])->name('appointments.complete');
    Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::get('/appointments/available-slots', [AppointmentController::class, 'getAvailableSlots'])->name('appointments.available-slots');
    
    // مسارات الاستعلامات - يجب أن تأتي قبل resource
    Route::get('/inquiry/search', [InquiryController::class, 'search'])->name('inquiry.search');
    Route::get('/inquiry/search/patients', [InquiryController::class, 'searchPatients'])->name('inquiry.search.patients');
    Route::get('/inquiry/occupancy', [InquiryController::class, 'occupancy'])->name('inquiry.occupancy');
    Route::resource('inquiry', InquiryController::class);
    
    // إدارة المشتريات والمستودع
    Route::prefix('suppliers')->name('suppliers.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SupplierController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\SupplierController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\SupplierController::class, 'store'])->name('store');
    });

    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [\App\Http\Controllers\ProductController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\ProductController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\ProductController::class, 'store'])->name('store');
        Route::get('/print-all', [\App\Http\Controllers\ProductController::class, 'printAllBarcodes'])->name('print-all');
        Route::get('/{product}/edit', [\App\Http\Controllers\ProductController::class, 'edit'])->name('edit');
        Route::put('/{product}', [\App\Http\Controllers\ProductController::class, 'update'])->name('update');
        Route::delete('/{product}', [\App\Http\Controllers\ProductController::class, 'destroy'])->name('destroy');
        Route::get('/{product}/barcode', [\App\Http\Controllers\ProductController::class, 'showBarcode'])->name('barcode');
    });

    Route::prefix('purchases')->name('purchases.')->group(function () {
        Route::get('/', [\App\Http\Controllers\PurchaseController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\PurchaseController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\PurchaseController::class, 'store'])->name('store');
        Route::get('/{purchase}', [\App\Http\Controllers\PurchaseController::class, 'show'])->name('show');
    });

    Route::prefix('barcodes')->name('barcodes.')->group(function () {
        Route::get('/', [\App\Http\Controllers\BarcodeController::class, 'index'])->name('index');
        Route::get('/batch/{batch}', [\App\Http\Controllers\BarcodeController::class, 'show'])->name('show');
        Route::get('/all', [\App\Http\Controllers\BarcodeController::class, 'printAll'])->name('all');
        Route::get('/purchase/{purchase}', [\App\Http\Controllers\BarcodeController::class, 'showPurchaseBarcodes'])->name('purchase');
        Route::post('/print-multiple', [\App\Http\Controllers\BarcodeController::class, 'printMultiple'])->name('print_multiple');
    });

    Route::get('/inventory', [\App\Http\Controllers\InventoryController::class, 'index'])
        ->middleware(['auth'])
        ->name('inventory.index');

    Route::get('/inventory/low-stock', [\App\Http\Controllers\InventoryController::class, 'lowStock'])
        ->middleware(['auth'])
        ->name('inventory.low_stock');

    Route::prefix('stock-transfers')->name('stock-transfers.')->group(function () {
        Route::get('/create', [\App\Http\Controllers\StockTransferController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\StockTransferController::class, 'store'])->name('store');
        Route::post('/requests', [\App\Http\Controllers\StockTransferController::class, 'storeRequest'])->name('requests.store');
        Route::get('/requests', [\App\Http\Controllers\StockTransferController::class, 'indexRequests'])->name('requests.index');
        Route::get('/requests/{stockTransferRequest}', [\App\Http\Controllers\StockTransferController::class, 'showRequest'])->name('requests.show');
        Route::post('/requests/{stockTransferRequest}/approve', [\App\Http\Controllers\StockTransferController::class, 'approveRequest'])->name('requests.approve');
        Route::post('/requests/{stockTransferRequest}/reject', [\App\Http\Controllers\StockTransferController::class, 'rejectRequest'])->name('requests.reject');
        Route::get('/returns/create', [\App\Http\Controllers\StockTransferController::class, 'createReturn'])->name('returns.create');
        Route::post('/returns', [\App\Http\Controllers\StockTransferController::class, 'storeReturn'])->name('returns.store');
    });

    Route::prefix('locations')->name('locations.')->group(function () {
        Route::get('/', [\App\Http\Controllers\LocationController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\LocationController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\LocationController::class, 'store'])->name('store');
        Route::get('/{location}', [\App\Http\Controllers\LocationController::class, 'show'])->name('show');
    });

    // مسارات الكاشير (Cashier Routes)
    Route::prefix('cashier')->name('cashier.')->group(function () {
        Route::get('/', [\App\Http\Controllers\CashierController::class, 'index'])->name('index');
        Route::get('/payment/{appointment}', [\App\Http\Controllers\CashierController::class, 'showPaymentForm'])->name('payment.form');
        Route::post('/payment/{appointment}', [\App\Http\Controllers\CashierController::class, 'processPayment'])->name('payment.process');
        Route::get('/request-payment/{request}', [\App\Http\Controllers\CashierController::class, 'showRequestPaymentForm'])->name('request.payment.form');
        Route::post('/request-payment/{request}', [\App\Http\Controllers\CashierController::class, 'processRequestPayment'])->name('request.payment.process');
        Route::get('/emergency-payment/{payment}', [\App\Http\Controllers\CashierController::class, 'showEmergencyPaymentForm'])->name('emergency.payment.form');
        Route::post('/emergency-payment/{payment}', [\App\Http\Controllers\CashierController::class, 'processEmergencyPayment'])->name('emergency.payment.process');
        Route::get('/emergency/payment-status', [\App\Http\Controllers\EmergencyController::class, 'paymentStatus'])->name('emergency.payment.status');
        Route::get('/surgeries', [\App\Http\Controllers\CashierController::class, 'surgeriesIndex'])->name('surgeries.index');
        Route::get('/surgeries/paid', [\App\Http\Controllers\CashierController::class, 'surgeriesPaid'])->name('surgeries.paid');
        Route::get('/surgeries/{surgery}/payment', [\App\Http\Controllers\CashierController::class, 'showSurgeryPaymentForm'])->name('surgeries.payment.form');
        Route::post('/surgeries/{surgery}/payment', [\App\Http\Controllers\CashierController::class, 'processSurgeryPayment'])->name('surgeries.payment.process');
        Route::get('/receipt/{payment}', [\App\Http\Controllers\CashierController::class, 'showReceipt'])->name('receipt');
        Route::get('/receipt/{payment}/print', [\App\Http\Controllers\CashierController::class, 'printReceipt'])->name('receipt.print');
        Route::get('/report', [\App\Http\Controllers\CashierController::class, 'paymentsReport'])->name('report');
        Route::get('/statements/export', [\App\Http\Controllers\CashierController::class, 'exportStatements'])->name('statements.export');
        Route::get('/statements', [\App\Http\Controllers\CashierController::class, 'statements'])->name('statements');
    });
    
    // مسارات نظام الحسابات (Accounting Routes)
    Route::prefix('accounting')->name('accounting.')->group(function () {
        Route::get('/', [\App\Http\Controllers\AccountingController::class, 'dashboard'])->name('dashboard');

        // المصروفات
        Route::prefix('expenses')->name('expenses.')->group(function () {
            Route::get('/', [\App\Http\Controllers\AccountingController::class, 'expensesIndex'])->name('index');
            Route::get('/create', [\App\Http\Controllers\AccountingController::class, 'createExpense'])->name('create');
            Route::post('/', [\App\Http\Controllers\AccountingController::class, 'storeExpense'])->name('store');
            Route::get('/{expense}/edit', [\App\Http\Controllers\AccountingController::class, 'editExpense'])->name('edit');
            Route::put('/{expense}', [\App\Http\Controllers\AccountingController::class, 'updateExpense'])->name('update');
            Route::delete('/{expense}', [\App\Http\Controllers\AccountingController::class, 'destroyExpense'])->name('destroy');
            Route::post('/{expense}/approve', [\App\Http\Controllers\AccountingController::class, 'approveExpense'])->name('approve');
            Route::post('/{expense}/reject', [\App\Http\Controllers\AccountingController::class, 'rejectExpense'])->name('reject');
        });

        // التقارير
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/revenue', [\App\Http\Controllers\AccountingController::class, 'revenueReport'])->name('revenue');
            Route::get('/expenses', [\App\Http\Controllers\AccountingController::class, 'expensesReport'])->name('expenses');
        });
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
        Route::put('/visits/{visit}/refer', [DoctorVisitController::class, 'referToDoctor'])->name('visits.refer');
        Route::post('/requests', [DoctorVisitController::class, 'storeRequest'])->name('requests.store');
        Route::match(['post', 'put'], '/requests/{request}', [DoctorVisitController::class, 'updateRequestStatus'])->name('requests.update');
        Route::put('/requests/{request}/status', [DoctorVisitController::class, 'updateRequestStatus'])->name('requests.update_status');
        Route::put('/appointments/{appointment}/convert', [DoctorVisitController::class, 'convertAppointmentToVisit'])->name('appointments.convert');
        
        // Patient Medical History Timeline
        Route::get('/patient/{patient}/history', [DoctorVisitController::class, 'showPatientHistory'])->name('patient.history');
    });
    
    // مسارات المريض للزيارات
    Route::prefix('patient')->name('patient.')->group(function () {
        Route::get('/visits', [PatientVisitController::class, 'index'])->name('visits.index');
        Route::get('/visits/{visit}', [PatientVisitController::class, 'show'])->name('visits.show');
    });
    
    // مسارات الموظفين الطبيين
    Route::prefix('staff')->name('staff.')->group(function () {
        // الطلبات (مشتركة - للروابط القديمة وللأدمن)
        Route::get('/requests/{type?}', [StaffRequestController::class, 'index'])->name('requests.index');
        Route::get('/requests/{request}/show', [StaffRequestController::class, 'show'])->name('requests.show');
        Route::put('/requests/{request}', [StaffRequestController::class, 'update'])->name('requests.update');
        Route::get('/requests/{request}/print', [StaffRequestController::class, 'print'])->name('requests.print');
        
        // زيارات المختبر
        Route::get('/lab-visits/create', [StaffRequestController::class, 'createLabVisit'])->name('lab-visits.create');
        Route::post('/lab-visits', [StaffRequestController::class, 'storeLabVisit'])->name('lab-visits.store');
        Route::post('/lab-requests/{request}/append-tests', [StaffRequestController::class, 'appendLabTests'])->name('lab-requests.append-tests');
        Route::delete('/lab-requests/{request}/tests/{labTest}', [StaffRequestController::class, 'removeLabTest'])->name('lab-requests.remove-test');
        
        // طلبات المختبر للعمليات
        Route::get('/surgery-lab-tests', [StaffRequestController::class, 'surgeryLabTests'])->name('surgery-lab-tests.index');
        Route::get('/surgery-lab-tests/selection', [StaffRequestController::class, 'surgeryLabTestsSelection'])->name('surgery-lab-tests.selection');
        Route::post('/surgery-lab-tests/{surgery}/create-selection', [StaffRequestController::class, 'createSurgeryLabTestSelection'])->name('surgery-lab-tests.create-selection');
        Route::get('/surgery-lab-tests/{test}', [StaffRequestController::class, 'showSurgeryLabTest'])->name('surgery-lab-tests.show');
        Route::put('/surgery-lab-tests/{test}', [StaffRequestController::class, 'updateSurgeryLabTest'])->name('surgery-lab-tests.update');
        Route::put('/surgery-lab-tests/{test}/select-tests', [StaffRequestController::class, 'selectTestsForSurgeryLabTest'])->name('surgery-lab-tests.select-tests');
        Route::put('/surgery/{surgery}/lab-tests/update-all', [StaffRequestController::class, 'updateAllSurgeryLabTests'])->name('surgery-lab-tests.update-all');
        Route::get('/surgery-lab-tests/{test}/print', [StaffRequestController::class, 'printSurgeryLabTest'])->name('surgery-lab-tests.print');
        
        // طلبات الأشعة للعمليات
        Route::get('/surgery-radiology-tests', [StaffRequestController::class, 'surgeryRadiologyTests'])->name('surgery-radiology-tests.index');
        Route::get('/surgery-radiology-tests/selection', [StaffRequestController::class, 'surgeryRadiologyTestsSelection'])->name('surgery-radiology-tests.selection');
        Route::post('/surgery-radiology-tests/{surgery}/create-selection', [StaffRequestController::class, 'createSurgeryRadiologyTestSelection'])->name('surgery-radiology-tests.create-selection');
        Route::get('/surgery-radiology-tests/{test}', [StaffRequestController::class, 'showSurgeryRadiologyTest'])->name('surgery-radiology-tests.show');
        Route::get('/surgery-radiology-tests/{test}/print', [StaffRequestController::class, 'printSurgeryRadiologyTest'])->name('surgery-radiology-tests.print');
        Route::put('/surgery-radiology-tests/{test}', [StaffRequestController::class, 'updateSurgeryRadiologyTest'])->name('surgery-radiology-tests.update');
        
        // طلبات الطوارئ - الأشعة
        Route::get('/emergency-radiology/{emergencyRadiology}', [StaffRequestController::class, 'showEmergencyRadiology'])->name('emergency-radiology.show');
        Route::get('/emergency-radiology/{emergencyRadiology}/print', [StaffRequestController::class, 'printEmergencyRadiology'])->name('emergency-radiology.print');
        Route::post('/emergency-radiology/{emergencyRadiology}/start', [StaffRequestController::class, 'startEmergencyRadiology'])->name('emergency-radiology.start');
        Route::put('/emergency-radiology/{emergencyRadiology}/complete', [StaffRequestController::class, 'completeEmergencyRadiology'])->name('emergency-radiology.complete');
        
        // طلبات الطوارئ - التحاليل
        Route::post('/emergency-lab/{emergencyLab}/start', [StaffRequestController::class, 'startEmergencyLab'])->name('emergency-lab.start');
        Route::put('/emergency-lab/{emergencyLab}/complete', [StaffRequestController::class, 'completeEmergencyLab'])->name('emergency-lab.complete');
        Route::get('/emergency-lab/{emergencyLab}/print', [StaffRequestController::class, 'printEmergencyLab'])->name('emergency-lab.print');
    });

    // ======= مسارات قسم المختبر =======
    Route::prefix('lab')->name('lab.')->middleware('can:view lab tests')->group(function () {
        Route::get('/requests', [\App\Http\Controllers\LabStaffController::class, 'index'])->name('index');
        Route::get('/requests/{request}/show', [\App\Http\Controllers\LabStaffController::class, 'show'])->name('show');
        Route::put('/requests/{request}', [\App\Http\Controllers\LabStaffController::class, 'update'])->name('update');
        Route::get('/requests/{request}/print', [\App\Http\Controllers\LabStaffController::class, 'print'])->name('print');
    });

    // ======= مسارات قسم الأشعة =======
    Route::prefix('radiology-staff')->name('radiology-staff.')->middleware('can:view radiology')->group(function () {
        Route::get('/requests', [\App\Http\Controllers\RadiologyStaffController::class, 'index'])->name('index');
        Route::get('/requests/{request}/show', [\App\Http\Controllers\RadiologyStaffController::class, 'show'])->name('show');
        Route::put('/requests/{request}', [\App\Http\Controllers\RadiologyStaffController::class, 'update'])->name('update');
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
        // إدارة مجموعات المفضلات الشخصية
        Route::middleware('can:view lab test groups')->group(function () {
            Route::get('/groups', [UserLabTestGroupController::class, 'index'])->name('groups.index');
            Route::post('/groups', [UserLabTestGroupController::class, 'store'])->name('groups.store')->middleware('can:create lab test groups');
            Route::get('/groups/{group}/edit', [UserLabTestGroupController::class, 'edit'])->name('groups.edit')->middleware('can:edit lab test groups');
            Route::put('/groups/{group}', [UserLabTestGroupController::class, 'update'])->name('groups.update')->middleware('can:edit lab test groups');
            Route::delete('/groups/{group}', [UserLabTestGroupController::class, 'destroy'])->name('groups.destroy')->middleware('can:delete lab test groups');
        });

        Route::get('/{labTest}', [\App\Http\Controllers\LabTestController::class, 'show'])->name('show');
        Route::get('/{labTest}/edit', [\App\Http\Controllers\LabTestController::class, 'edit'])->name('edit');
        Route::put('/{labTest}', [\App\Http\Controllers\LabTestController::class, 'update'])->name('update');
        Route::delete('/{labTest}', [\App\Http\Controllers\LabTestController::class, 'destroy'])->name('destroy');
        Route::post('/{labTest}/toggle-status', [\App\Http\Controllers\LabTestController::class, 'toggleStatus'])->name('toggle-status');
        Route::post('/{labTest}/toggle-favorite', [\App\Http\Controllers\LabTestController::class, 'toggleFavorite'])->name('toggle-favorite');

        // إدارة القيم المرجعية
        Route::get('/{labTest}/references', [\App\Http\Controllers\LabTestReferenceController::class, 'index'])->name('references.index');
        Route::post('/{labTest}/references', [\App\Http\Controllers\LabTestReferenceController::class, 'store'])->name('references.store');
        Route::put('/{labTest}/references/{reference}', [\App\Http\Controllers\LabTestReferenceController::class, 'update'])->name('references.update');
        Route::delete('/{labTest}/references/{reference}', [\App\Http\Controllers\LabTestReferenceController::class, 'destroy'])->name('references.destroy');
    });

    // إدارة باقات المختبر (Admin)
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::resource('doctor-commission-settings', \App\Http\Controllers\Admin\DoctorCommissionSettingController::class)->except(['show']);
        Route::post('doctor-commission-settings/save', [\App\Http\Controllers\Admin\DoctorCommissionSettingController::class, 'save'])
            ->name('doctor-commission-settings.save');
        Route::resource('packages', \App\Http\Controllers\Admin\PackageController::class)->except(['show']);
    });

    // إدارة العمليات
    Route::get('/surgeries/waiting', [SurgeryController::class, 'waitingList'])->name('surgeries.waiting');
    Route::post('/surgeries/{surgery}/check-in', [SurgeryController::class, 'checkIn'])->name('surgeries.check-in');
    Route::get('/surgeries/{surgery}/print', [SurgeryController::class, 'print'])->name('surgeries.print');
    Route::resource('surgeries', SurgeryController::class);
    Route::patch('/surgeries/{surgery}/update-details', [SurgeryController::class, 'updateDetails'])->name('surgeries.updateDetails');
    Route::post('/surgeries/{surgery}/start', [SurgeryController::class, 'start'])->name('surgeries.start');
    Route::post('/surgeries/{surgery}/complete', [SurgeryController::class, 'complete'])->name('surgeries.complete');
    Route::post('/surgeries/{surgery}/discharge', [SurgeryController::class, 'discharge'])->name('surgeries.discharge');
    Route::post('/surgeries/{surgery}/cancel', [SurgeryController::class, 'cancel'])->name('surgeries.cancel');
    Route::post('/surgeries/{surgery}/return-to-waiting', [SurgeryController::class, 'returnToWaiting'])->name('surgeries.return-to-waiting');
    
    // محطات العمليات
    Route::prefix('surgery-stations')->group(function () {
        // محطة الجراح
        Route::middleware('can:view surgeon station')->group(function () {
            Route::get('/surgeon', [\App\Http\Controllers\SurgeonStationController::class, 'index'])->name('surgeon-station.index');
            Route::get('/surgeon/{surgery}', [\App\Http\Controllers\SurgeonStationController::class, 'show'])->name('surgeon-station.show');
            Route::get('/surgeon/{surgery}/resident-follow-ups', [\App\Http\Controllers\SurgeonStationController::class, 'residentFollowUps'])->name('surgeon-station.resident-follow-ups');
            Route::patch('/surgeon/{surgery}', [\App\Http\Controllers\SurgeonStationController::class, 'update'])->name('surgeon-station.update');
            Route::post('/surgeon/{surgery}/complete', [\App\Http\Controllers\SurgeonStationController::class, 'complete'])->name('surgeon-station.complete');
        });
        
        // محطة التخدير
        Route::middleware('can:view anesthesia station')->group(function () {
            Route::get('/anesthesia', [\App\Http\Controllers\AnesthesiaStationController::class, 'index'])->name('anesthesia-station.index');
            Route::get('/anesthesia/{surgery}', [\App\Http\Controllers\AnesthesiaStationController::class, 'show'])->name('anesthesia-station.show');
            Route::patch('/anesthesia/{surgery}', [\App\Http\Controllers\AnesthesiaStationController::class, 'update'])->name('anesthesia-station.update');
            Route::post('/anesthesia/{surgery}/complete', [\App\Http\Controllers\AnesthesiaStationController::class, 'complete'])->name('anesthesia-station.complete');
        });
        
        // محطة المقيم
        Route::middleware('can:view resident station')->group(function () {
            Route::get('/resident', [\App\Http\Controllers\ResidentStationController::class, 'index'])->name('resident-station.index');
            Route::get('/resident/{surgery}', [\App\Http\Controllers\ResidentStationController::class, 'show'])->name('resident-station.show');
            Route::patch('/resident/{surgery}', [\App\Http\Controllers\ResidentStationController::class, 'update'])->name('resident-station.update');
            Route::post('/resident/{surgery}/complete', [\App\Http\Controllers\ResidentStationController::class, 'complete'])->name('resident-station.complete');
            Route::post('/resident/{surgery}/follow-ups', [\App\Http\Controllers\ResidentStationController::class, 'storeFollowUp'])->name('resident-station.follow-ups.store');
        });
        
        // محطة صالة العمليات
        Route::middleware('can:view operation theater station')->group(function () {
            Route::get('/operation-theater', [\App\Http\Controllers\OperationTheaterStationController::class, 'index'])->name('operation-theater-station.index');
            Route::get('/operation-theater/{surgery}', [\App\Http\Controllers\OperationTheaterStationController::class, 'show'])->name('operation-theater-station.show');
            Route::patch('/operation-theater/{surgery}', [\App\Http\Controllers\OperationTheaterStationController::class, 'update'])->name('operation-theater-station.update');
            Route::post('/operation-theater/{surgery}/complete', [\App\Http\Controllers\OperationTheaterStationController::class, 'complete'])->name('operation-theater-station.complete');
        });
        
        // محطة التمريض
        Route::middleware('can:view nursing station')->group(function () {
            Route::get('/nursing', [\App\Http\Controllers\NursingStationController::class, 'index'])->name('nursing-station.index');
            Route::get('/nursing/{surgery}', [\App\Http\Controllers\NursingStationController::class, 'show'])->name('nursing-station.show');
            Route::patch('/nursing/{surgery}', [\App\Http\Controllers\NursingStationController::class, 'update'])->name('nursing-station.update');
            Route::post('/nursing/{surgery}/complete', [\App\Http\Controllers\NursingStationController::class, 'complete'])->name('nursing-station.complete');
            Route::post('/nursing/{surgery}/follow-ups', [\App\Http\Controllers\NursingStationController::class, 'storeFollowUp'])->name('nursing-station.follow-ups.store');
        });

        // إعطاء العلاج (مشترك للمقيم والتمريض)
        Route::post('/treatments/{treatment}/administer', [\App\Http\Controllers\ResidentStationController::class, 'administerTreatment'])->name('surgery-treatments.administer');
    });
    
    // إضافة طبيب مرسل جديد
    Route::post('/doctors/store-referring', [\App\Http\Controllers\DoctorController::class, 'storeReferringDoctor'])->name('doctors.store-referring');

    // إدارة الغرف
    Route::resource('rooms', \App\Http\Controllers\RoomController::class);
    Route::post('/rooms/{room}/change-status', [\App\Http\Controllers\RoomController::class, 'changeStatus'])->name('rooms.change-status');
    Route::get('/api/rooms/available', [\App\Http\Controllers\RoomController::class, 'getAvailable'])->name('api.rooms.available');

    // إدارة حاضنات الخدج
    Route::resource('incubators', \App\Http\Controllers\IncubatorController::class);
    Route::post('/incubators/{incubator}/toggle-maintenance', [\App\Http\Controllers\IncubatorController::class, 'toggleMaintenance'])
         ->name('incubators.toggle-maintenance');
    
    // إدارة حجوزات الحاضنات
    Route::prefix('incubator-reservations')->name('incubator-reservations.')->group(function () {
        Route::get('/', [\App\Http\Controllers\IncubatorReservationController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\IncubatorReservationController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\IncubatorReservationController::class, 'store'])->name('store');
        Route::get('/occupied', [\App\Http\Controllers\IncubatorReservationController::class, 'occupied'])->name('occupied');
        Route::get('/{incubatorReservation}', [\App\Http\Controllers\IncubatorReservationController::class, 'show'])->name('show');
        Route::patch('/{incubatorReservation}/admit', [\App\Http\Controllers\IncubatorReservationController::class, 'admit'])->name('admit');
        Route::patch('/{incubatorReservation}/discharge', [\App\Http\Controllers\IncubatorReservationController::class, 'discharge'])->name('discharge');
        Route::patch('/{incubatorReservation}/cancel', [\App\Http\Controllers\IncubatorReservationController::class, 'cancel'])->name('cancel');
        Route::post('/{incubatorReservation}/transfer', [\App\Http\Controllers\IncubatorReservationController::class, 'transfer'])->name('transfer');
    });

    // إدارة أجور العمليات الجراحية
    Route::prefix('surgical-operations')->name('surgical-operations.')->group(function () {
        Route::get('/', [\App\Http\Controllers\SurgicalOperationController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\SurgicalOperationController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\SurgicalOperationController::class, 'store'])->name('store');
        Route::get('/trashed', [\App\Http\Controllers\SurgicalOperationController::class, 'trashed'])->name('trashed');
        Route::patch('/{surgicalOperation}/restore', [\App\Http\Controllers\SurgicalOperationController::class, 'restore'])->name('restore');
        Route::delete('/{surgicalOperation}', [\App\Http\Controllers\SurgicalOperationController::class, 'destroy'])->name('destroy');
    });

    // إدارة الطوارئ
    Route::prefix('emergency')->name('emergency.')->group(function () {
        Route::get('/', [\App\Http\Controllers\EmergencyController::class, 'index'])->name('index');
        Route::get('/dashboard', [\App\Http\Controllers\EmergencyController::class, 'dashboard'])->name('dashboard');
        Route::get('/create', [\App\Http\Controllers\EmergencyController::class, 'create'])->name('create');
        Route::post('/', [\App\Http\Controllers\EmergencyController::class, 'store'])->name('store');
        Route::get('/{emergency}', [\App\Http\Controllers\EmergencyController::class, 'show'])->name('show');
        Route::get('/{emergency}/edit', [\App\Http\Controllers\EmergencyController::class, 'edit'])->name('edit');
        Route::put('/{emergency}', [\App\Http\Controllers\EmergencyController::class, 'update'])->name('update');
        Route::delete('/{emergency}', [\App\Http\Controllers\EmergencyController::class, 'destroy'])->name('destroy');
        Route::post('/{emergency}/vitals', [\App\Http\Controllers\EmergencyController::class, 'updateVitals'])->name('update-vitals');
        Route::post('/{emergency}/start-treatment', [\App\Http\Controllers\EmergencyController::class, 'startTreatment'])->name('start-treatment');
        Route::post('/{emergency}/complete', [\App\Http\Controllers\EmergencyController::class, 'complete'])->name('complete');
        Route::post('/{emergency}/update-medical', [\App\Http\Controllers\EmergencyController::class, 'updateMedical'])->name('update-medical');
        Route::post('/{emergency}/treatments', [\App\Http\Controllers\EmergencyController::class, 'storeTreatment'])->name('treatments.store');
        Route::post('/{emergency}/create-consultation', [\App\Http\Controllers\EmergencyController::class, 'createConsultation'])->name('create-consultation');
        Route::post('/{emergency}/request-lab', [\App\Http\Controllers\EmergencyController::class, 'requestLab'])->name('request-lab');
        Route::post('/{emergency}/request-radiology', [\App\Http\Controllers\EmergencyController::class, 'requestRadiology'])->name('request-radiology');
        
        // تحديث حالة طلبات الخدمات التمريضية
        Route::put('/nursing-request/{request}', [\App\Http\Controllers\EmergencyController::class, 'updateNursingRequest'])->name('nursing-request.update');

        // emergency patient records
        Route::get('/patients', [\App\Http\Controllers\EmergencyPatientController::class, 'index'])->name('patients.index');
        Route::get('/patients/{patient}', [\App\Http\Controllers\EmergencyPatientController::class, 'show'])->name('patients.show');
        Route::post('/patients/{patient}/migrate', [\App\Http\Controllers\EmergencyPatientController::class, 'migrate'])->name('patients.migrate');
    });
});
