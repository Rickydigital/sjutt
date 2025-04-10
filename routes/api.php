<?php
use App\Http\Controllers\FirebaseNotificationController;
use App\Http\Controllers\Mobile\NewsController;
use App\Http\Controllers\Mobile\TimetableController;
use App\Http\Controllers\VenueController;

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
});

Route::middleware('auth:sanctum')->post('/store-token', [NewsController::class, 'storeToken']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [NewsController::class, 'logout']);
    Route::get('/profile', [NewsController::class, 'profile']);
    Route::put('/profile', [NewsController::class, 'editProfile']);
    Route::post('/change-password', [NewsController::class, 'changePassword']);
});
Route::post('/forgot-password', [NewsController::class, 'forgotPassword']);