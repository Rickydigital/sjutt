<?php

use App\Http\Controllers\Mobile\AuthController;
use App\Http\Controllers\Mobile\ChatController;
use App\Http\Controllers\Mobile\EventController;
use App\Http\Controllers\Mobile\GalleryController;
use App\Http\Controllers\Mobile\NewsController;
use App\Http\Controllers\Mobile\TalentController;
use App\Http\Controllers\Mobile\TimetableController;
use App\Http\Controllers\SuggestionController;
use App\Http\Controllers\VenueController;
use App\Http\Controllers\Mobile\CalendarController;
use App\Http\Controllers\FeeStructureController;
use App\Http\Controllers\FirebaseNotificationController;

Route::get('/fee_structures', [FeeStructureController::class, 'index']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/request-registration-otp', [AuthController::class, 'requestRegistrationOtp']);
Route::post('/verify-registration-otp', [AuthController::class, 'verifyRegistrationOtp']);
Route::post('/login', [NewsController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/request-otp', [AuthController::class, 'requestOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/calendar', [CalendarController::class, 'index']);

Route::get('/news', [NewsController::class, 'index']);
Route::get('/news/latest', [NewsController::class, 'latest']);
Route::get('/news/{id}', [NewsController::class, 'show']);
Route::get('/venues', [VenueController::class, 'apiIndex']);
Route::get('/faculties', [NewsController::class, 'getFaculties']);
Route::get('/timetables/lecture', [TimetableController::class, 'getLectureTimetables']);
Route::get('/timetables/examination', [TimetableController::class, 'getExaminationTimetables']);
Route::post('send-notification', [FirebaseNotificationController::class, 'sendNotification']);
Route::get('/galleries', [GalleryController::class, 'index']);
Route::get('/galleries/latest', [GalleryController::class, 'latest']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/news/{id}/comment', [NewsController::class, 'comment']);
    Route::post('/news/{id}/react', [NewsController::class, 'react']);
    Route::delete('/news/{id}/react', [NewsController::class, 'removeReaction']);
    Route::post('/suggestions', [SuggestionController::class, 'store']);
    Route::get('/suggestions', [SuggestionController::class, 'index']);
    Route::delete('/suggestions/{id}', [SuggestionController::class, 'delete']);
    Route::post('/verify-password', [SuggestionController::class, 'verifyPassword']);
    Route::get('/talents', [TalentController::class, 'index']);
    Route::post('/talents', [TalentController::class, 'store']);
    Route::post('/talents/{id}/like', [TalentController::class, 'like']);
    Route::post('/talents/{id}/comment', [TalentController::class, 'comment']);
    Route::get('/my-talents', [TalentController::class, 'myTalents']);
    Route::delete('/talents/{id}', [TalentController::class, 'delete']);
    Route::get('/events', [EventController::class, 'index']);
    Route::get('/events/latest', [EventController::class, 'latest']);
    Route::get('/events/{id}', [EventController::class, 'show']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'editProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/store-token', [AuthController::class, 'storeToken']);
    Route::post('/update-online-status', [AuthController::class, 'updateOnlineStatus']);
    Route::get('/users', [ChatController::class, 'getUsers']);
    Route::get('/chat/messages', [ChatController::class, 'getMessages']);
    Route::post('/chat/messages', [ChatController::class, 'sendMessage']);
    Route::delete('/chat/messages/{id}', [ChatController::class, 'deleteMessage']);
    Route::get('/chat/stats', [ChatController::class, 'getStats']);
    Route::post('/update-last-chat-access', [ChatController::class, 'updateLastChatAccess']);
});