<?php

use App\Http\Controllers\Api\V1\AppointmentTypeController;
use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth:sanctum');

Route::prefix('/v1')->name('v1.')->group(function () {
    // Auth

    Route::group(['middleware' => 'auth:sanctum'], function () {
        // Appointment types
       Route::get('/appointment-type', [AppointmentTypeController::class, 'index'])->name('appointment-type.index');
       Route::get('/appointment-type/{appointmentType}', [AppointmentTypeController::class, 'show'])->name('appointment-type.show');
       Route::post('/appointment-type', [AppointmentTypeController::class, 'store'])->name('appointment-type.store');
       Route::patch('/appointment-type/{appointmentType}', [AppointmentTypeController::class, 'update'])->name('appointment-type.update');
       Route::delete('/appointment-type/{appointmentType}', [AppointmentTypeController::class, 'destroy'])->name('appointment-type.destroy');
    });
});
