<?php

use App\Http\Controllers\Eye\EyeCashierController;
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

    // 1. استعلامات واستقبال وطابور العيون
    Route::prefix('reception')->name('reception.')->group(function () {
        Route::get('/', [EyeReceptionController::class, 'index'])->name('index');
        Route::get('/create', [EyeReceptionController::class, 'create'])->name('create');
        Route::post('/store', [EyeReceptionController::class, 'store'])->name('store');
        Route::patch('/appointments/{appointment}/status', [EyeReceptionController::class, 'updateStatus'])->name('updateStatus');
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

    // 6. عمليات وإجراءات وحقن العيون
    Route::prefix('surgeries')->name('surgeries.')->group(function () {
        Route::get('/', [EyeSurgeryController::class, 'index'])->name('index');
        Route::get('/create', [EyeSurgeryController::class, 'create'])->name('create');
        Route::post('/store', [EyeSurgeryController::class, 'store'])->name('store');
        Route::get('/{surgery}', [EyeSurgeryController::class, 'show'])->name('show');
        Route::patch('/{surgery}/status', [EyeSurgeryController::class, 'updateStatus'])->name('updateStatus');
    });

});
