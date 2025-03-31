<?php

use App\Http\Controllers\Mobile\NewsController as ApiNewsController;
use App\Http\Controllers\Mobile\EventController as ApiEventController;
use App\Http\Controllers\API\FAQController as APIFAQController;
use App\Http\Controllers\API\GalleryController as APIGalleryController;
use App\Http\Controllers\API\AboutController as APIAboutController;
use App\Http\Controllers\API\FeeStructureController as APIFeeStructureController;
use App\Http\Controllers\API\CourseController as APICourseController;
use App\Http\Controllers\Mobile\CalendarController;
use App\Http\Controllers\Mobile\QueryController;

Route::post('/queries', [QueryController::class, 'create']);
Route::get('/calendars', [CalendarController::class, 'fetch'])->name('mobile.calendars.fetch');

Route::get('news', [ApiNewsController::class, 'index']);
Route::get('news/{news}', [ApiNewsController::class, 'show']);
Route::post('news/{news}/like', [ApiNewsController::class, 'like']);
Route::post('news/{news}/dislike', [ApiNewsController::class, 'dislike']);
Route::post('news/{news}/comment', [ApiNewsController::class, 'comment']);

Route::get('events', [ApiEventController::class, 'index']);
Route::get('events/{event}', [ApiEventController::class, 'show']);
Route::post('events/{event}/like', [ApiEventController::class, 'like']);
Route::post('events/{event}/dislike', [ApiEventController::class, 'dislike']);
Route::post('events/{event}/comment', [ApiEventController::class, 'comment']);

// FAQ Routes (Mobile App)
Route::get('faqs', [APIFAQController::class, 'index']);  // Fetch all FAQs
Route::get('faq/{id}', [APIFAQController::class, 'show']); // Fetch single FAQ
Route::post('faq/rate/{id}', [APIFAQController::class, 'rate']); // Rate FAQ

// Gallery Routes (Mobile App)
Route::get('gallery', [APIGalleryController::class, 'index']);  // Fetch all gallery items
Route::get('gallery/{id}', [APIGalleryController::class, 'show']);  // Fetch single gallery item
Route::post('gallery/like/{id}', [APIGalleryController::class, 'like']);  // Like gallery item
Route::post('gallery/unlike/{id}', [APIGalleryController::class, 'unlike']);  // Unlike gallery item

// About Routes (Mobile App)
Route::get('about', [APIAboutController::class, 'index']); // Fetch about info

// Fee Structure Routes (Mobile App)
Route::get('fee-structures', [APIFeeStructureController::class, 'index']);  // Fetch all fee structures
Route::get('fee-structure/{id}', [APIFeeStructureController::class, 'show']);  // Fetch single fee structure

// Course Routes (Mobile App)
Route::get('courses', [APICourseController::class, 'index']);  // Fetch all courses
Route::get('course/{id}', [APICourseController::class, 'show']);  // Fetch single course