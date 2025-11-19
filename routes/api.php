<?php

use App\Http\Controllers\Mobile\CourseController;
use App\Http\Controllers\Mobile\VideoController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Mobile\AuthController;
use App\Http\Controllers\Mobile\ChatController;
use App\Http\Controllers\Mobile\EventController;
use App\Http\Controllers\Mobile\GalleryController;
use App\Http\Controllers\Mobile\NewsController;
use App\Http\Controllers\Mobile\TalentController;
use App\Http\Controllers\Mobile\TimetableController;
use App\Http\Controllers\Mobile\VenueController;
use App\Http\Controllers\SuggestionController;
use App\Http\Controllers\Mobile\CalendarController;
use App\Http\Controllers\FeeStructureController;
use App\Http\Controllers\FirebaseNotificationController;
use App\Http\Controllers\MobileController;

Route::get('/fee_structures', [FeeStructureController::class, 'index']);
Route::get('/get-programs', [AuthController::class, 'getPrograms']);
Route::get('/stream/video/{folder}/{filename}', [VideoController::class, 'stream'])->name('video.stream');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/request-otp', [AuthController::class, 'requestOtp']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);
Route::get('/calendar', [CalendarController::class, 'index']);




Route::middleware('mobile-auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/request-registration-otp', [AuthController::class, 'requestRegistrationOtp']);
    Route::post('/verify-registration-otp', [AuthController::class, 'verifyRegistrationOtp']);
    Route::get('/venues', [VenueController::class, 'apiIndex']);
    Route::get('/faculties', [NewsController::class, 'getFaculties']);
    Route::get('/courses', [CourseController::class, 'getAllCourses']);
    Route::get('/venue-timetables', [TimetableController::class, 'getVenueTimetables']);
    Route::get('/timetables/lecture', [TimetableController::class, 'getLectureTimetables']);
    Route::get('/timetables/examination', [TimetableController::class, 'getExaminationTimetables']);
    Route::post('send-notification', [FirebaseNotificationController::class, 'sendNotification']);
    Route::get('/gallery', [GalleryController::class, 'getGallery']);
    Route::get('/news', [NewsController::class, 'getNews']);
    Route::get('/events', [EventController::class, 'getEvents']);
    Route::post('/news/{id}/comment', [NewsController::class, 'comment']);
    Route::post('/news/{id}/react', [NewsController::class, 'react']);
    Route::delete('/news/{id}/react', [NewsController::class, 'removeReaction']);
    Route::get('/suggestions', [SuggestionController::class, 'index']);
    Route::delete('/suggestions/{id}', [SuggestionController::class, 'delete']);
    Route::post('/verify-password', [SuggestionController::class, 'verifyPassword']);
    Route::get('/talents', [TalentController::class, 'index']);
    Route::post('/talents', [TalentController::class, 'store']);
    Route::post('/talents/{id}/like', [TalentController::class, 'like']);
    Route::post('/talents/{id}/comment', [TalentController::class, 'comment']);
    Route::get('/my-talents', [TalentController::class, 'myTalents']);
    Route::delete('/talents/{id}', [TalentController::class, 'delete']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/store-token', [AuthController::class, 'storeToken']);
    Route::post('/update-online-status', [AuthController::class, 'updateOnlineStatus']);
    Route::get('/users', [ChatController::class, 'getUsers']);
    Route::get('/chat/messages', [ChatController::class, 'getMessages']);
    Route::post('/chat/messages', [ChatController::class, 'sendMessage']);
    Route::delete('/chat/messages/{id}', [ChatController::class, 'deleteMessage']);
    Route::get('/chat/stats', [ChatController::class, 'getStats']);
    Route::post('/update-last-chat-access', [ChatController::class, 'updateLastChatAccess']);
    Route::get('/get-venues', [VenueController::class, 'getVenues']);
    Route::post('/search-venue', [VenueController::class, 'searchVenue']);
});

//!routes protected by default sanctum middleware
Route::middleware('auth:sanctum')->group(function (): void {
    Route::put('/edit-profile', [AuthController::class, 'editProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/suggestions', [SuggestionController::class, 'store']);
});

