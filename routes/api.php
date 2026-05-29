<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LocationController;

// Public Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/verify-email', [AuthController::class, 'verifyEmail']);
Route::post('/resend-verification', [AuthController::class, 'resendVerification']);

// Protected routes
Route::middleware('auth.jwt')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Admin only routes
    Route::middleware('role:admin')->group(function () {
        Route::get('/employees', [EmployeeController::class, 'index']);
        Route::post('/employees', [EmployeeController::class, 'store']);
        Route::delete('/employees/{id}', [EmployeeController::class, 'destroy']);

        Route::get('/deleted-employees', [EmployeeController::class, 'deletedEmployees']);
        Route::post('/restore-employee/{id}', [EmployeeController::class, 'restore']);
        Route::delete('/permanent-delete-employee/{id}', [EmployeeController::class, 'forceDelete']);

        Route::get('/employees-export', [EmployeeController::class, 'export']);
        Route::post('/employees-import', [EmployeeController::class, 'import']);
    });

    // Employee specific/mutual routes (secured in controller)
    Route::get('/employees/{id}', [EmployeeController::class, 'show']);
    Route::put('/employees/{id}', [EmployeeController::class, 'update']);

    // Document Management routes
    Route::get('/employees/{id}/documents', [EmployeeController::class, 'getDocuments']);
    Route::post('/employees/{id}/documents', [EmployeeController::class, 'uploadDocuments']);
    Route::get('/documents/{id}', [EmployeeController::class, 'getDocument']);
    Route::put('/documents/{id}', [EmployeeController::class, 'updateDocument']);
    Route::delete('/documents/{id}', [EmployeeController::class, 'deleteDocument']);

    // Location routes
    Route::get('/countries', [LocationController::class, 'getCountries']);
    Route::get('/countries/{countryId}/states', [LocationController::class, 'getStates']);
    Route::get('/states/{stateId}/cities', [LocationController::class, 'getCities']);
});
