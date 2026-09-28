<?php

use App\Http\Controllers\Eye\EyeCashierController;
use App\Http\Controllers\Eye\EyeDoctorAvailabilityController;
use App\Http\Controllers\Eye\EyeExaminationController;
use App\Http\Controllers\Eye\EyeInvestigationController;
use App\Http\Controllers\Eye\EyeReceptionController;
use App\Http\Controllers\Eye\EyeStoreController;
use App\Http\Controllers\Eye\EyeSurgeryController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| مسارات قسم ومركز العيون التخصصي (Ophthalmology Center Routes)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('eye')->name('eye.')->group(function () {

    // 0. توفر أطباء واستشاريي العيون وطابور المراجعين المباشر وإدارة أطباء العيون
    Route::prefix('availability')->name('availability.')->group(function () {
        Route::get('/', [EyeDoctorAvailabilityController::class, 'index'])->name('index');
        Route::get('/doctors/create', [EyeDoctorAvailabilityController::class, 'createDoctor'])->name('doctors.create');
        Route::post('/doctors/store', [EyeDoctorAvailabilityController::class, 'storeDoctor'])->name('doctors.store');
        Route::get('/doctors/{doctor}/edit', [EyeDoctorAvailabilityController::class, 'editDoctor'])->name('doctors.edit');
        Route::put('/doctors/{doctor}', [EyeDoctorAvailabilityController::class, 'updateDoctorSettings'])->name('doctors.updateSettings');
        Route::post('/update/{doctor}', [EyeDoctorAvailabilityController::class, 'update'])->name('update');
        Route::post('/bulk-update', [EyeDoctorAvailabilityController::class, 'bulkUpdate'])->name('bulkUpdate');
        Route::post('/call-patient/{appointment}', [EyeDoctorAvailabilityController::class, 'callPatient'])->name('callPatient');
        Route::post('/admit-patient/{appointment}', [EyeDoctorAvailabilityController::class, 'admitPatient'])->name('admitPatient');
        Route::post('/dilate-patient/{appointment}', [EyeDoctorAvailabilityController::class, 'dilatePatient'])->name('dilatePatient');
    });

    // 1. استعلامات واستقبال وطابور العيون
    Route::prefix('reception')->name('reception.')->group(function () {
        Route::get('/', [EyeReceptionController::class, 'index'])->name('index');
        Route::get('/create', [EyeReceptionController::class, 'create'])->name('create');
        Route::post('/store', [EyeReceptionController::class, 'store'])->name('store');
        Route::patch('/appointments/{appointment}/status', [EyeReceptionController::class, 'updateStatus'])->name('updateStatus');
        Route::get('/appointments/{appointment}/ticket', [EyeReceptionController::class, 'printTicket'])->name('printTicket');
        Route::get('/search-patients', [EyeReceptionController::class, 'searchPatients'])->name('searchPatients');
    });

    // 2. كاشير ووصولات العيون
    Route::prefix('cashier')->name('cashier.')->group(function () {
        Route::get('/', [EyeCashierController::class, 'index'])->name('index');
        Route::get('/invoices/{invoice}', [EyeCashierController::class, 'show'])->name('show');
        Route::post('/invoices/{invoice}/items', [EyeCashierController::class, 'addItem'])->name('addItem');
        Route::post('/invoices/{invoice}/pay', [EyeCashierController::class, 'processPayment'])->name('pay');
        Route::get('/invoices/{invoice}/receipt', [EyeCashierController::class, 'printReceipt'])->name('printReceipt');
        Route::post('/daily-reconciliation', [EyeCashierController::class, 'dailyReconciliation'])->name('dailyReconciliation');
    });

    // 3. مخزن مستلزمات وعدسات العيون
    Route::prefix('store')->name('store.')->group(function () {
        Route::get('/', [EyeStoreController::class, 'index'])->name('index');
        Route::post('/items', [EyeStoreController::class, 'store'])->name('items.store');
        Route::post('/direct-purchase', [EyeStoreController::class, 'directPurchase'])->name('directPurchase');
        Route::post('/transfer-requisition', [EyeStoreController::class, 'createTransferRequisition'])->name('transferRequisition');
        Route::post('/transfers/{transferRequest}/receive', [EyeStoreController::class, 'receiveTransfer'])->name('receiveTransfer');
        Route::post('/dispense', [EyeStoreController::class, 'dispenseItem'])->name('dispense');
    });

    // 4. محطة فحص وكشف العيون السريرية
    Route::prefix('examinations')->name('examinations.')->group(function () {
        Route::get('/', [EyeExaminationController::class, 'index'])->name('index');
        Route::get('/workstation', [EyeExaminationController::class, 'create'])->name('create');
        Route::post('/store', [EyeExaminationController::class, 'store'])->name('store');
        Route::get('/{examination}', [EyeExaminationController::class, 'show'])->name('show');
        Route::get('/{examination}/glasses-print', [EyeExaminationController::class, 'printGlasses'])->name('printGlasses');
    });

    // 5. فحوصات الأجهزة (OCT, الساحة البصرية, Pentacam, Biometry)
    Route::prefix('investigations')->name('investigations.')->group(function () {
        Route::get('/', [EyeInvestigationController::class, 'index'])->name('index');
        Route::get('/create', [EyeInvestigationController::class, 'create'])->name('create');
        Route::post('/store', [EyeInvestigationController::class, 'store'])->name('store');
        Route::get('/{investigation}', [EyeInvestigationController::class, 'show'])->name('show');
        Route::post('/{investigation}/results', [EyeInvestigationController::class, 'updateResults'])->name('updateResults');
    });

    // 6. عمليات وزرع عدسات وحقن العيون
    Route::prefix('surgeries')->name('surgeries.')->group(function () {
        Route::get('/', [EyeSurgeryController::class, 'index'])->name('index');
        Route::get('/create', [EyeSurgeryController::class, 'create'])->name('create');
        Route::post('/store', [EyeSurgeryController::class, 'store'])->name('store');
        Route::get('/{surgery}', [EyeSurgeryController::class, 'show'])->name('show');
        Route::patch('/{surgery}/status', [EyeSurgeryController::class, 'updateStatus'])->name('updateStatus');
    });

    // 7. صالة الانتظار والشاشات
    Route::prefix('queue')->name('queue.')->group(function () {
        Route::get('/all-clinics', [\App\Http\Controllers\Eye\EyeQueueController::class, 'allClinicsDisplay'])->name('all-clinics.display');
        Route::get('/all-clinics/data', [\App\Http\Controllers\Eye\EyeQueueController::class, 'allClinicsData'])->name('all-clinics.data');
    });

});
