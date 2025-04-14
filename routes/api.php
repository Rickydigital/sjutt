<?php

use App\Http\Controllers\FirebaseNotificationController;
use App\Http\Controllers\Mobile\NewsController;
use App\Http\Controllers\Mobile\TimetableController;
use App\Http\Controllers\SuggestionController;
use App\Http\Controllers\VenueController;
use App\Http\Controllers\Mobile\CalendarController;

Route::post('/register', [NewsController::class, 'register']);
Route::post('/login', [NewsController::class, 'login']);

// Public routes (no authentication required)
Route::get('/news', [NewsController::class, 'index']);
Route::get('/news/{id}', [NewsController::class, 'show']);
Route::get('/venues', [VenueController::class, 'apiIndex']);
Route::get('/faculties', [NewsController::class, 'getFaculties']);
Route::get('/timetables/lecture', [TimetableController::class, 'getLectureTimetables']);
Route::get('/timetables/examination', [TimetableController::class, 'getExaminationTimetables']);
Route::post('send-notification', [FirebaseNotificationController::class, 'sendNotification']);

// Authenticated routes (require Sanctum authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/news/{id}/comment', [NewsController::class, 'comment']);
    Route::post('/news/{id}/react', [NewsController::class, 'react']);
    Route::post('/suggestions', [SuggestionController::class, 'store']);
    Route::get('/suggestions', [SuggestionController::class, 'index']);
});

Route::middleware('auth:sanctum')->post('/verify-password', [SuggestionController::class, 'verifyPassword']);
Route::middleware('auth:sanctum')->post('/store-token', [NewsController::class, 'storeToken']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [NewsController::class, 'logout']);
    Route::get('/profile', [NewsController::class, 'profile']);
    Route::put('/profile', [NewsController::class, 'editProfile']);
    Route::post('/change-password', [NewsController::class, 'changePassword']);
    Route::get('/calendar', [CalendarController::class, 'index']);
    Route::post('/update-online-status', [NewsController::class, 'updateOnlineStatus']);
});
Route::post('/forgot-password', [NewsController::class, 'forgotPassword']);