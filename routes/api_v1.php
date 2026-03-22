<?php

use App\Http\Controllers\Api\V1\AppointmentController;
use App\Http\Controllers\Api\V1\AppointmentTypeController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DentistBlockedSlotController;
use App\Http\Controllers\Api\V1\DentistController;
use App\Http\Controllers\Api\V1\PatientController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth:sanctum');

Route::prefix('/v1')->name('v1.')->group(function () {

    Route::group(['middleware' => 'auth:sanctum'], function () {
        // Appointment types
        Route::get('/appointment-type', [AppointmentTypeController::class, 'index'])->name('appointment-type.index');
        Route::get('/appointment-type/{appointmentType}', [AppointmentTypeController::class, 'show'])->name('appointment-type.show');
        Route::post('/appointment-type', [AppointmentTypeController::class, 'store'])->name('appointment-type.store');
        Route::patch('/appointment-type/{appointmentType}', [AppointmentTypeController::class, 'update'])->name('appointment-type.update');
        Route::delete('/appointment-type/{appointmentType}', [AppointmentTypeController::class, 'destroy'])->name('appointment-type.destroy');

        Route::get('/patient', [PatientController::class, 'index'])->name('patient.index');
        Route::get('/patient/{patient}', [PatientController::class, 'show'])->name('patient.show');
        Route::post('/patient', [PatientController::class, 'store'])->name('patient.store');
        Route::patch('/patient/{patient}', [PatientController::class, 'update'])->name('patient.update');
        Route::delete('/patient/{patient}', [PatientController::class, 'destroy'])->name('patient.destroy');
        Route::get('/patient/{patient}/appointments', [PatientController::class, 'appointments'])->name('patient.appointments');

        Route::get('/dentist', [DentistController::class, 'index'])->name('dentist.index');
        Route::get('/dentist/{dentist}', [DentistController::class, 'show'])->name('dentist.show');
        Route::post('/dentist', [DentistController::class, 'store'])->name('dentist.store');
        Route::patch('/dentist/{dentist}', [DentistController::class, 'update'])->name('dentist.update');
        Route::delete('/dentist/{dentist}', [DentistController::class, 'destroy'])->name('dentist.destroy');
        Route::get('/dentist/{dentist}/availability', [DentistController::class, 'availability'])->name('dentist.availability');

        Route::get('/dentist/{dentist}/blocked-slots', [DentistBlockedSlotController::class, 'index'])->name('dentist.blocked-slots.index');
        Route::post('/dentist/{dentist}/blocked-slots', [DentistBlockedSlotController::class, 'store'])->name('dentist.blocked-slots.store');
        Route::delete('/dentist/{dentist}/blocked-slots/{dentistBlockedSlot}', [DentistBlockedSlotController::class, 'destroy'])->name('dentist.blocked-slots.destroy');

        Route::get('/appointment', [AppointmentController::class, 'index'])->name('appointment.index');
        Route::get('/appointment/{appointment}', [AppointmentController::class, 'show'])->name('appointment.show');
        Route::post('/appointment', [AppointmentController::class, 'store'])->name('appointment.store');
        Route::patch('/appointment/{appointment}', [AppointmentController::class, 'update'])->name('appointment.update');
        Route::delete('/appointment/{appointment}', [AppointmentController::class, 'destroy'])->name('appointment.destroy');
        Route::post('/appointment/{appointment}/confirm', [AppointmentController::class, 'confirm'])->name('appointment.confirm');
        Route::post('/appointment/{appointment}/complete', [AppointmentController::class, 'complete'])->name('appointment.complete');
        Route::post('/appointment/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointment.cancel');
    });
});
