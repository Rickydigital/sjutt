<?php

use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\QueryController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\AdminSuggestionController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\ExaminationTimetableController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\AboutController;
use App\Http\Controllers\Admin\FeeStructureController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\YearController;
use App\Http\Controllers\VenueController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/timetable/pdf', [TimetableController::class, 'pdf'])->name('timetable.pdf');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/queries', [QueryController::class, 'index'])->name('admin.queries.index');
    Route::post('/queries/{query}/progress', [QueryController::class, 'addProgress'])->name('admin.queries.addProgress');
    Route::get('/calendars', [CalendarController::class, 'index'])->name('admin.calendars.index');
    Route::get('/calendars/create', [CalendarController::class, 'create'])->name('admin.calendars.create');
    Route::post('/calendars', [CalendarController::class, 'store'])->name('admin.calendars.store');
    Route::get('/calendars/{id}/edit', [CalendarController::class, 'edit'])->name('admin.calendars.edit');
    Route::put('/calendars/{id}', [CalendarController::class, 'update'])->name('admin.calendars.update');
    Route::delete('/calendars/{id}', [CalendarController::class, 'destroy'])->name('admin.calendars.destroy');
    Route::post('/calendars/import', [CalendarController::class, 'import'])->name('admin.calendars.import');
    Route::get('/timetables/export-all-pdf', [ExaminationTimetableController::class, 'exportAllPdf'])->name('timetables.export.all.pdf');
    Route::get('timetables/import', [ExaminationTimetableController::class, 'importView'])->name('timetables.import.view');
    Route::post('timetables/import', [ExaminationTimetableController::class, 'import'])->name('timetables.import');
    Route::resource('timetables', ExaminationTimetableController::class);
    Route::get('/timetable', [TimetableController::class, 'index'])->name('timetable.index');
    Route::get('/timetable/create', [TimetableController::class, 'create'])->name('timetable.create');
    Route::post('/timetable', [TimetableController::class, 'store'])->name('timetable.store');
    Route::get('/timetable/{id}/edit', [TimetableController::class, 'edit'])->name('timetable.edit');
    Route::put('/timetable/{id}', [TimetableController::class, 'update'])->name('timetable.update');
    Route::delete('/timetable/{id}', [TimetableController::class, 'destroy'])->name('timetable.destroy');
    Route::post('/timetable/import', [TimetableController::class, 'import'])->name('timetable.import');
    Route::resource('faqs', FaqController::class);
    Route::resource('gallery', GalleryController::class);
    Route::resource('about', AboutController::class);
    Route::resource('fee_structures', FeeStructureController::class);
    Route::resource('courses', CourseController::class);
    Route::resource('users', UserController::class);
    Route::resource('news', NewsController::class);
    Route::resource('events', EventController::class);
    Route::resource('faculties', FacultyController::class);
    Route::resource('years', YearController::class);
    Route::resource('venues', VenueController::class);
    Route::put('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
    Route::put('users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
    Route::get('/suggestions', [AdminSuggestionController::class, 'index'])->name('admin.suggestions.index');
    Route::get('/suggestions/{suggestion}', [AdminSuggestionController::class, 'show'])->name('admin.suggestions.show');
    Route::put('/suggestions/{suggestion}/status', [AdminSuggestionController::class, 'updateStatus'])->name('admin.suggestions.updateStatus');
   
    

});



require __DIR__.'/auth.php';
