<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\RentalController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'show'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('login.store');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
Route::middleware('auth')->group(function () {
    Route::get('/', [RentalController::class, 'dashboard'])->name('dashboard');
    Route::get('/comprobantes/{payment}/archivo', [RentalController::class, 'voucher'])->name('voucher');
    Route::get('/pagos/{payment}/recibo', [RentalController::class, 'receipt'])->name('receipts.show');
    Route::middleware('role:inquilino')->group(function () {
        Route::post('/facturas/{invoice}/pago', [RentalController::class, 'uploadPayment'])->name('payments.upload');
    });
    Route::middleware('role:administrador')->group(function () {
        Route::get('/habitaciones', [RentalController::class, 'rooms'])->name('rooms');
        Route::post('/habitaciones', [RentalController::class, 'saveRoom'])->name('rooms.store');
        Route::put('/habitaciones/{room}', [RentalController::class, 'saveRoom'])->name('rooms.update');
        Route::delete('/habitaciones/{room}', [RentalController::class, 'deleteRoom'])->name('rooms.delete');
        Route::get('/inquilinos', [RentalController::class, 'tenants'])->name('tenants');
        Route::post('/inquilinos', [RentalController::class, 'saveTenant'])->name('tenants.store');
        Route::put('/inquilinos/{tenant}', [RentalController::class, 'saveTenant'])->name('tenants.update');
        Route::delete('/inquilinos/{tenant}', [RentalController::class, 'deleteTenant'])->name('tenants.delete');
        Route::get('/contratos', [RentalController::class, 'contracts'])->name('contracts');
        Route::post('/contratos', [RentalController::class, 'saveContract'])->name('contracts.store');
        Route::put('/contratos/{contract}', [RentalController::class, 'saveContract'])->name('contracts.update');
        Route::delete('/contratos/{contract}', [RentalController::class, 'deleteContract'])->name('contracts.delete');
        Route::get('/facturas', [RentalController::class, 'invoices'])->name('invoices');
        Route::post('/facturas', [RentalController::class, 'saveInvoice'])->name('invoices.store');
        Route::put('/facturas/{invoice}', [RentalController::class, 'saveInvoice'])->name('invoices.update');
        Route::patch('/facturas/{invoice}/anular', [RentalController::class, 'voidInvoice'])->name('invoices.void');
        Route::get('/pagos', [RentalController::class, 'payments'])->name('payments');
        Route::post('/pagos/{payment}/revision', [RentalController::class, 'reviewPayment'])->name('payments.review');
        Route::get('/reportes', [RentalController::class, 'reports'])->name('reports');
    });
});
