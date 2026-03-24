<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Api\V1\ChecklistController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\PackingListController;
use App\Http\Controllers\SuggestionsController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:5,1'])->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

Route::get('/test', function () {
    return response()->json(['message' => 'API funcionando']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/user', [AuthController::class, 'updateUser']);
    Route::post('/user/avatar', [AuthController::class, 'uploadAvatar'])->name('api.user.avatar');

    // FCM Token and Notification Preferences routes
    Route::post('/user/fcm-token', [UserController::class, 'storeFcmToken']);
    Route::delete('/user/fcm-token', [UserController::class, 'deleteFcmToken']);
    Route::put('/user/notification-preferences', [UserController::class, 'updateNotificationPreferences']);

    // All activities for the authenticated user
    Route::get('/activities', [ActivityController::class, 'all']);

    Route::apiResource('trips', TripController::class);
    Route::get('trips/{trip}/export/itinerary.pdf', [ExportController::class, 'itineraryPdf'])->name('api.trips.export.itinerary');
    Route::get('trips/{trip}/export/expenses.csv', [ExportController::class, 'expensesCsv'])->name('api.trips.export.expenses');
    Route::post('trips/{trip}/suggestions', [SuggestionsController::class, 'suggest'])->name('api.trips.suggestions');

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
    Route::get('trips/{trip}/expenses/{expense}/receipt', [ExpenseController::class, 'downloadReceipt'])->name('api.expenses.receipt');

    Route::get('trips/{trip}/documents', [DocumentController::class, 'index']);
    Route::post('trips/{trip}/documents', [DocumentController::class, 'store']);
    Route::delete('documents/{document}', [DocumentController::class, 'destroy']);

    // Document Checklist routes
    Route::get('trips/{trip}/checklist', [ChecklistController::class, 'show']);
    Route::post('trips/{trip}/checklist/items', [ChecklistController::class, 'addItem']);
    Route::patch('checklist/items/{item}/complete', [ChecklistController::class, 'toggleComplete']);
    Route::delete('checklist/items/{item}', [ChecklistController::class, 'deleteItem']);
    Route::post('checklist/items/{item}/documents', [ChecklistController::class, 'uploadDocument']);
    Route::post('checklist/items/{item}/documents/from-drive', [ChecklistController::class, 'importFromDrive']);
    Route::get('checklist/documents/{document}/download', [ChecklistController::class, 'downloadDocument']);
    Route::get('checklist/documents/{document}/preview', [ChecklistController::class, 'previewDocument']);
    Route::delete('checklist/documents/{document}', [ChecklistController::class, 'deleteDocument']);

    // Packing List routes
    Route::get('trips/{trip}/packing-list', [PackingListController::class, 'show']);
    Route::post('trips/{trip}/packing-list/generate', [PackingListController::class, 'generate']);
    Route::post('trips/{trip}/packing-list/weather-suggestions', [PackingListController::class, 'weatherSuggestions']);
    Route::post('trips/{trip}/packing-list/items', [PackingListController::class, 'addItem']);
    Route::patch('packing-list/items/{item}/toggle', [PackingListController::class, 'toggleItem']);
    Route::delete('packing-list/items/{item}', [PackingListController::class, 'deleteItem']);
});
