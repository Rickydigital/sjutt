<?php

use App\Http\Controllers\Admin\AlumniElectionController;
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
use App\Http\Controllers\LecturerCourseController;
use App\Http\Controllers\Mobile\Alumni\AlumniAuthController;
use App\Http\Controllers\Mobile\Alumni\AlumniContentController;
use App\Http\Controllers\Mobile\Alumni\AlumniElectionController as MobileAlumniElectionController;
use App\Http\Controllers\Mobile\Alumni\AlumniLookupController;
use App\Http\Controllers\Mobile\AppVersionController;
use App\Http\Controllers\Mobile\StaffAuthController;
use App\Http\Controllers\Mobile\ElectionVotingController;
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
Route::post('/forget-password', [AuthController::class, 'forgetPassword']);
Route::post('/verify-reset-password-otp', [AuthController::class, 'verifyResetPasswordOtp']);
Route::get('/app/latest-version', [AppVersionController::class, 'latest']);

//staff
Route::post('/staff/login', [StaffAuthController::class, 'login']);
Route::post('/staff/forget-password', [StaffAuthController::class, 'forgetPassword']);
Route::post('/staff/verify-reset-password-otp', [StaffAuthController::class, 'verifyResetPasswordOtp']);

//Alumin
Route::prefix('alumni')->group(function (): void {
    // Lookup data (public — used for registration and profile completion dropdowns)
    Route::get('/lookups', [AlumniLookupController::class, 'index']);

    // Public auth flow
    Route::post('/check-email', [AlumniAuthController::class, 'checkEmail']);
    Route::post('/register', [AlumniAuthController::class, 'register']);
    Route::post('/first-login', [AlumniAuthController::class, 'firstLogin']);
    Route::post('/login', [AlumniAuthController::class, 'login']);

    // Password reset flow
    Route::post('/forgot-password', [AlumniAuthController::class, 'forgotPassword']);
    Route::post('/verify-reset-password-otp', [AlumniAuthController::class, 'verifyResetPasswordOtp']);

    // Requires reset token returned by verify-reset-password-otp
    Route::post(
        '/reset-password',
        [AlumniAuthController::class, 'resetPassword']
    );

    // Authenticated alumni routes
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/profile', [AlumniAuthController::class, 'profile']);
        Route::post('/profile/update', [AlumniAuthController::class, 'updateProfile']);
        Route::post('/change-password', [AlumniAuthController::class, 'changePassword']);
        Route::post('/store-token', [AlumniAuthController::class, 'storeToken']);
        Route::post('/logout', [AlumniAuthController::class, 'logout']);
    });
});
Route::middleware('auth:sanctum')->prefix('alumni/elections')->name('api.alumni.elections.')->group(function () {
    Route::get('/', [MobileAlumniElectionController::class, 'elections'])->name('index');
    Route::get('/{alumniElection}', [MobileAlumniElectionController::class, 'show'])->name('show');
    Route::post('/{alumniElection}/apply', [MobileAlumniElectionController::class, 'apply'])->name('apply');
    Route::get('/me/applications', [MobileAlumniElectionController::class, 'myApplications'])->name('my-applications');
    Route::get('/{alumniElection}/voting', [MobileAlumniElectionController::class, 'voting'])->name('voting');
    Route::post('/{alumniElection}/vote', [MobileAlumniElectionController::class, 'vote'])->name('vote');
    Route::get('/{alumniElection}/results', [MobileAlumniElectionController::class, 'results'])->name('results');
});

Route::prefix('alumni')->name('api.alumni.')->group(function () {
    Route::get('/events', [AlumniContentController::class, 'events']);
    Route::get('/events/{event}', [AlumniContentController::class, 'eventShow']);
    Route::get('/calendar', [AlumniContentController::class, 'calendars']);
    Route::get('/posts', [AlumniContentController::class, 'posts']);
    Route::get('/posts/{post}', [AlumniContentController::class, 'postShow']);

    // Later, when alumni are allowed to share posts, keep this route protected.
    Route::middleware('auth:sanctum')->post('/posts', [AlumniContentController::class, 'submitPost']);
});


Route::middleware('mobile-auth')->group(function (): void {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/request-registration-otp', [AuthController::class, 'requestRegistrationOtp']);
    Route::post('/resend-registration-otp', [AuthController::class, 'resendRegistrationOtp']);
    Route::post('/verify-registration-otp', [AuthController::class, 'verifyRegistrationOtp']);
    Route::post('/update-fcm-token', [AuthController::class, 'updateFcmToken']);
    Route::get('/venues', [VenueController::class, 'apiIndex']);
    Route::get('/faculties', [NewsController::class, 'getFaculties']);
    Route::get('/courses', [CourseController::class, 'getAllCourses']);
    Route::get('/venue-timetables', [TimetableController::class, 'getVenueTimetables']);
    Route::get('/timetables/lecture', [TimetableController::class, 'getLectureTimetables']);
    Route::get('/timetables/examination', [TimetableController::class, 'getExaminationTimetables']);
    Route::get('/lecturer-timetable', [TimetableController::class, 'getLecturerTimetables']);
    Route::get('/course-students', [TimetableController::class, 'getCourseStudents']);
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
    Route::get('/lecturer/courses', [LecturerCourseController::class, 'index']);
    Route::get('/elections', [ElectionVotingController::class, 'elections']);
    Route::get('/elections/voting', [ElectionVotingController::class, 'index']);
    Route::post('/elections/vote', [ElectionVotingController::class, 'store']);
    Route::get('/elections/my-votes', [ElectionVotingController::class, 'myVotes']);
    Route::get('/elections/{election}/results', [ElectionVotingController::class, 'results']);
});

//!routes protected by default sanctum middleware
Route::middleware('auth:sanctum')->group(function (): void {
    Route::put('/edit-profile', [AuthController::class, 'editProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::post('/suggestions', [SuggestionController::class, 'store']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);
});

//staff guard from auth configuration

Route::middleware('auth:staff-api')->group(function () {
    Route::get('/staff/profile', [StaffAuthController::class, 'profile']);
    Route::post('/staff/edit-profile', [StaffAuthController::class, 'editProfile']);
    Route::post('/staff/change-password', [StaffAuthController::class, 'changePassword']);
    Route::post('/staff/store-token', [StaffAuthController::class, 'storeToken']);
    Route::post('/staff/update-online-status', [StaffAuthController::class, 'updateOnlineStatus']);
    Route::post('/staff/logout', [StaffAuthController::class, 'logout']);
    Route::post('/staff/reset-password', [StaffAuthController::class, 'resetPassword']);
    Route::post('/staff/request-phone-otp', [StaffAuthController::class, 'requestPhoneVerificationOtp']);
    Route::post('/staff/verify-phone-otp', [StaffAuthController::class, 'verifyPhoneOtp']);
    Route::post('/staff/resend-phone-otp', [StaffAuthController::class, 'resendPhoneOtp']);
   

});
