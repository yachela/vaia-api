<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\TripController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::get('/test', function () {
    return response()->json(['message' => 'API funcionando']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user', [AuthController::class, 'updateUser']);

    Route::apiResource('trips', TripController::class);

    Route::get('trips/{trip}/activities', [ActivityController::class, 'index']);
    Route::post('trips/{trip}/activities', [ActivityController::class, 'store']);
    Route::get('trips/{trip}/activities/{activity}', [ActivityController::class, 'show']);
    Route::put('trips/{trip}/activities/{activity}', [ActivityController::class, 'update']);
    Route::delete('trips/{trip}/activities/{activity}', [ActivityController::class, 'destroy']);

    Route::get('trips/{trip}/expenses', [ExpenseController::class, 'index']);
    Route::post('trips/{trip}/expenses', [ExpenseController::class, 'store']);
    Route::get('trips/{trip}/expenses/{expense}', [ExpenseController::class, 'show']);
    Route::put('trips/{trip}/expenses/{expense}', [ExpenseController::class, 'update']);
    Route::delete('trips/{trip}/expenses/{expense}', [ExpenseController::class, 'destroy']);

    Route::get('trips/{trip}/documents', [DocumentController::class, 'index']);
    Route::post('trips/{trip}/documents', [DocumentController::class, 'store']);
    Route::delete('documents/{document}', [DocumentController::class, 'destroy']);
});
