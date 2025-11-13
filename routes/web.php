<?php

use App\Http\Controllers\Admin\NewsController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\QueryController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\AdminSuggestionController;
use App\Http\Controllers\TimetableController;
use App\Http\Controllers\ExaminationTimetableController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\GalleryController;
use App\Http\Controllers\Admin\AboutController;
use App\Http\Controllers\Admin\FeeStructureController;
use App\Http\Controllers\BuildingController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FacultyController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\YearController;
use App\Http\Controllers\VenueController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\IptController;
use App\Http\Controllers\AlumniController;
use App\Http\Controllers\EnrolledCourseController;
use App\Http\Controllers\ExaminationController;
use App\Http\Controllers\LibraryController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\ResearchController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CrossCatingTimetableController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TalentController;
use App\Http\Controllers\TimetableSemesterController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('auth');

Route::middleware(['web', 'auth'])->group(function () {
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit')->middleware(['permission:view own profile']);
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update')->middleware(['permission:edit own profile']);
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy')->middleware(['permission:delete own profile']);

    // Fee Structures
    Route::get('/fee_structures/download-template', [FeeStructureController::class, 'downloadTemplate'])->name('fee_structures.download_template')->middleware(['permission:export fee structures']);
    Route::post('/fee_structures/import', [FeeStructureController::class, 'import'])->name('fee_structures.import')->middleware(['permission:import fee structures']);
    Route::get('/fee_structures', [FeeStructureController::class, 'index'])->name('fee_structures.index')->middleware(['permission:view fee structures']);
    Route::get('/fee_structures/create', [FeeStructureController::class, 'create'])->name('fee_structures.create')->middleware(['permission:view fee structures']);
    Route::post('/fee_structures', [FeeStructureController::class, 'store'])->name('fee_structures.store')->middleware(['permission:create fee structures']);
    Route::get('/fee_structures/{fee_structure}', [FeeStructureController::class, 'show'])->name('fee_structures.show')->middleware(['permission:view fee structures']);
    Route::get('/fee_structures/{fee_structure}/edit', [FeeStructureController::class, 'edit'])->name('fee_structures.edit')->middleware(['permission:view fee structures']);
    Route::put('/fee_structures/{fee_structure}', [FeeStructureController::class, 'update'])->name('fee_structures.update')->middleware(['permission:edit fee structures']);
    Route::delete('/fee_structures/{fee_structure}', [FeeStructureController::class, 'destroy'])->name('fee_structures.destroy')->middleware(['permission:delete fee structures']);

    // Timetables
    Route::get('/timetable/pdf', [TimetableController::class, 'pdf'])->name('timetable.pdf')->middleware(['permission:export timetables']);
    Route::post('/timetable/import', [TimetableController::class, 'import'])->name('timetable.import')->middleware(['permission:import timetables']);
    Route::get('/timetable/export', [TimetableController::class, 'export'])->name('timetable.export')->middleware(['permission:export timetables']);
    Route::get('/timetables/faculties', [TimetableController::class, 'getAllFaculties'])->name('timetables.getAllFaculties')->middleware(['permission:view timetables']);
    Route::get('/timetables/faculties-by-program', [TimetableController::class, 'getFacultiesByProgram'])->name('timetables.getFacultiesByProgram')->middleware(['permission:view programs']);
    Route::get('/timetables/courses', [TimetableController::class, 'getFacultyCourses'])->name('timetables.getCourses')->middleware(['permission:view courses']);
    Route::get('/timetables/groups', [TimetableController::class, 'getFacultyGroups'])->name('timetables.getGroups')->middleware(['permission:view examination timetables']);
    Route::get('/timetables/lecturers', [TimetableController::class, 'getCourseLecturers'])->name('timetables.getLecturers')->middleware(['permission:view examination timetables']);
    Route::get('/timetable', [TimetableController::class, 'index'])->name('timetable.index')->middleware(['permission:view timetables']);
    Route::get('/timetable/create', [TimetableController::class, 'create'])->name('timetable.create')->middleware(['permission:view timetables']);
    Route::post('/timetable', [TimetableController::class, 'store'])->name('timetable.store')->middleware(['permission:create timetables']);
    Route::get('/timetable/{timetable}', [TimetableController::class, 'show'])->name('timetable.show')->middleware(['permission:view timetables']);
    Route::get('/timetable/{timetable}/edit', [TimetableController::class, 'edit'])->name('timetable.edit')->middleware(['permission:view timetables']);
    Route::put('/timetable/{timetable}', [TimetableController::class, 'update'])->name('timetable.update')->middleware(['permission:edit timetables']);
    Route::delete('/timetable/{timetable}', [TimetableController::class, 'destroy'])->name('timetable.destroy')->middleware(['permission:delete timetables']);
    Route::get('/cross-cating-timetable', [CrossCatingTimetableController::class, 'index'])->name('cross-cating.index');
    Route::post('/cross-cating-timetable/generate/{courseId}', [CrossCatingTimetableController::class, 'generateForCourse'])->name('cross-cating-timetable.generate');
    Route::post('/timetable-semesters', [TimetableSemesterController::class, 'store'])->name('timetable-semesters.store');
    Route::put('/timetable-semesters/first', [TimetableSemesterController::class, 'update'])->name('timetable-semesters.update');
    Route::get('/timetable-semesters/first', [TimetableSemesterController::class, 'show'])->name('timetable-semesters.show');
    // Examination Timetables
    Route::post('/timetables/generate', [ExaminationTimetableController::class, 'generateTimetable'])->name('timetables.generate');
    Route::get('/timetables/faculty/{program_id}/{year_num}', [ExaminationTimetableController::class, 'getFacultyByProgramYear'])->name('timetables.getFacultyByProgramYear')->middleware(['permission:view examination timetables']);
    Route::get('/timetables/faculty-courses', [ExaminationTimetableController::class, 'getFacultyCourses'])->name('timetables.getFacultyCourses')->middleware(['permission:view examination timetables']);
    Route::get('/timetables/faculty-groups', [ExaminationTimetableController::class, 'getFacultyGroups'])->name('timetables.getFacultyGroups')->middleware(['permission:view examination timetables']);
    Route::get('/timetables/course-lecturers', [ExaminationTimetableController::class, 'getCourseLecturers'])->name('timetables.getCourseLecturers')->middleware(['permission:view examination timetables']);
    Route::post('/timetables/setup', [ExaminationTimetableController::class, 'storeSetup'])->name('timetables.storeSetup')->middleware(['permission:view examination timetables']);
    Route::get('/timetables/pdf', [ExaminationTimetableController::class, 'generatePdf'])->name('timetables.pdf')->middleware(['permission:export examination timetables']);
    Route::get('/timetables/import', [ExaminationTimetableController::class, 'importView'])->name('timetables.import.view')->middleware(['permission:import examination timetables']);
    Route::post('/timetables/import', [ExaminationTimetableController::class, 'import'])->name('timetables.import')->middleware(['permission:import examination timetables']);
    Route::put('/timetables/setup/{setup}', [ExaminationTimetableController::class, 'updateSetup'])->name('timetables.updateSetup')->middleware(['permission:edit examination timetables']);
    Route::get('/timetables/create', [ExaminationTimetableController::class, 'export'])->name('timetables.export')->middleware(['permission:export examination timetables']);
    Route::post('/timetables/student-count', [TimetableController::class, 'getStudentCount'])->name('timetables.getStudentCount')->middleware(['permission:view examination timetables']);
    Route::get('/timetables', [ExaminationTimetableController::class, 'index'])->name('timetables.index')->middleware(['permission:view examination timetables']);
    Route::get('/timetables/create', [ExaminationTimetableController::class, 'create'])->name('timetables.create')->middleware(['permission:view examination timetables']);
    Route::post('/timetables', [ExaminationTimetableController::class, 'store'])->name('timetables.store')->middleware(['permission:create examination timetables']);
    Route::get('/timetables/{timetable}', [ExaminationTimetableController::class, 'show'])->name('timetables.show')->middleware(['permission:view examination timetables']);
    Route::get('/timetables/{timetable}/edit', [ExaminationTimetableController::class, 'edit'])->name('timetables.edit')->middleware(['permission:view examination timetables']);
    Route::put('/timetables/{timetable}', [ExaminationTimetableController::class, 'update'])->name('timetables.update')->middleware(['permission:edit examination timetables']);
    Route::delete('/timetables/{timetable}', [ExaminationTimetableController::class, 'destroy'])->name('timetables.destroy')->middleware(['permission:delete examination timetables']);
    Route::post('/timetable/generate', [TimetableController::class, 'generateTimetable'])->name('timetable.generate');
    Route::post('/timetables/generate', [ExaminationTimetableController::class, 'generateTimetable'])->name('timetables.generate');

    // Faculties
    Route::post('/faculties/store-course', [FacultyController::class, 'storeCourse'])->name('faculties.storeCourse')->middleware(['permission:create courses']);
    Route::get('/faculties/names', [FacultyController::class, 'getFacultyNames'])->name('faculties.getFacultyNames')->middleware(['permission:view faculties']);
    Route::post('/faculties/import', [FacultyController::class, 'import'])->name('faculties.import')->middleware(['permission:import faculties']);
    Route::get('/faculties/export', [FacultyController::class, 'export'])->name('faculties.export')->middleware(['permission:export faculties']);
    Route::get('/faculties', [FacultyController::class, 'index'])->name('faculties.index')->middleware(['permission:view faculties']);
    Route::get('/faculties/create', [FacultyController::class, 'create'])->name('faculties.create')->middleware(['permission:view faculties']);
    Route::post('/faculties', [FacultyController::class, 'store'])->name('faculties.store')->middleware(['permission:create faculties']);
    Route::get('/faculties/{faculty}', [FacultyController::class, 'show'])->name('faculties.show')->middleware(['permission:view faculties']);
    Route::get('/faculties/{faculty}/edit', [FacultyController::class, 'edit'])->name('faculties.edit')->middleware(['permission:view faculties']);
    Route::put('/faculties/{faculty}', [FacultyController::class, 'update'])->name('faculties.update')->middleware(['permission:edit faculties']);
    Route::delete('/faculties/{faculty}', [FacultyController::class, 'destroy'])->name('faculties.destroy')->middleware(['permission:delete faculties']);

    // FAQs
    Route::get('/faqs', [FaqController::class, 'index'])->name('faqs.index')->middleware(['permission:view faqs']);
    Route::get('/faqs/create', [FaqController::class, 'create'])->name('faqs.create')->middleware(['permission:view faqs']);
    Route::post('/faqs', [FaqController::class, 'store'])->name('faqs.store')->middleware(['permission:create faqs']);
    Route::get('/faqs/{faq}', [FaqController::class, 'show'])->name('faqs.show')->middleware(['permission:view faqs']);
    Route::get('/faqs/{faq}/edit', [FaqController::class, 'edit'])->name('faqs.edit')->middleware(['permission:view faqs']);
    Route::put('/faqs/{faq}', [FaqController::class, 'update'])->name('faqs.update')->middleware(['permission:edit faqs']);
    Route::delete('/faqs/{faq}', [FaqController::class, 'destroy'])->name('faqs.destroy')->middleware(['permission:delete faqs']);

    // Gallery
    Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery.index')->middleware(['permission:view gallery']);
    Route::get('/gallery/create', [GalleryController::class, 'create'])->name('gallery.create')->middleware(['permission:view gallery']);
    Route::post('/gallery', [GalleryController::class, 'store'])->name('gallery.store')->middleware(['permission:create gallery']);
    Route::get('/gallery/{gallery}', [GalleryController::class, 'show'])->name('gallery.show')->middleware(['permission:view gallery']);
    Route::get('/gallery/{gallery}/edit', [GalleryController::class, 'edit'])->name('gallery.edit')->middleware(['permission:view gallery']);
    Route::put('/gallery/{gallery}', [GalleryController::class, 'update'])->name('gallery.update')->middleware(['permission:edit gallery']);
    Route::delete('/gallery/{gallery}', [GalleryController::class, 'destroy'])->name('gallery.destroy')->middleware(['permission:delete gallery']);

    // About
    Route::get('/about', [AboutController::class, 'index'])->name('about.index')->middleware(['permission:view about']);
    Route::get('/about/create', [AboutController::class, 'create'])->name('about.create')->middleware(['permission:view about']);
    Route::post('/about', [AboutController::class, 'store'])->name('about.store')->middleware(['permission:create about']);
    Route::get('/about/{about}', [AboutController::class, 'show'])->name('about.show')->middleware(['permission:view about']);
    Route::get('/about/{about}/edit', [AboutController::class, 'edit'])->name('about.edit')->middleware(['permission:view about']);
    Route::put('/about/{about}', [AboutController::class, 'update'])->name('about.update')->middleware(['permission:edit about']);
    Route::delete('/about/{about}', [AboutController::class, 'destroy'])->name('about.destroy')->middleware(['permission:delete about']);

    // Courses
    Route::get('/courses/export', [CourseController::class, 'export'])->name('courses.export')->middleware(['permission:export courses']);
    Route::post('/courses/import', [CourseController::class, 'import'])->name('courses.import')->middleware(['permission:import courses']);
    Route::get('/courses', [CourseController::class, 'index'])->name('courses.index')->middleware(['permission:view courses']);
    Route::get('/courses/create', [CourseController::class, 'create'])->name('courses.create')->middleware(['permission:view courses']);
    Route::post('/courses', [CourseController::class, 'store'])->name('courses.store')->middleware(['permission:create courses']);
    Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show')->middleware(['permission:view courses']);
    Route::get('/courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit')->middleware(['permission:view courses']);
    Route::put('/courses/{course}', [CourseController::class, 'update'])->name('courses.update')->middleware(['permission:edit courses']);
    Route::delete('/courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy')->middleware(['permission:delete courses']);

    // Users
    Route::get('/user-sessions', [UserController::class, 'sessionsIndex'])->name('user.sessions.index');
    Route::get('/user-sessions/{user}', [UserController::class, 'sessionsShow'])->name('user.sessions.show');
    Route::get('/user-sessions/{user}/pdf', [UserController::class, 'sessionsPdf'])->name('user.sessions.pdf');
    Route::post('users/import', [UserController::class, 'import'])->name('users.import');
    Route::put('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate')->middleware(['permission:deactivate users']);
    Route::put('/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate')->middleware(['permission:activate users']);
    Route::get('/users', [UserController::class, 'index'])->name('users.index')->middleware(['permission:view users']);
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create')->middleware(['permission:view users']);
    Route::post('/users', [UserController::class, 'store'])->name('users.store')->middleware(['permission:create users']);
    Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show')->middleware(['permission:view users']);
    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit')->middleware(['permission:view users']);
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update')->middleware(['permission:edit users']);
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy')->middleware(['permission:delete users']);

    // News
    Route::get('/news', [NewsController::class, 'index'])->name('news.index')->middleware(['permission:view news']);
    Route::get('/news/create', [NewsController::class, 'create'])->name('news.create')->middleware(['permission:view news']);
    Route::post('/news', [NewsController::class, 'store'])->name('news.store')->middleware(['permission:create news']);
    Route::get('/news/{news}', [NewsController::class, 'show'])->name('news.show')->middleware(['permission:view news']);
    Route::get('/news/{news}/edit', [NewsController::class, 'edit'])->name('news.edit')->middleware(['permission:view news']);
    Route::put('/news/{news}', [NewsController::class, 'update'])->name('news.update')->middleware(['permission:edit news']);
    Route::delete('/news/{news}', [NewsController::class, 'destroy'])->name('news.destroy')->middleware(['permission:delete news']);

    // Events
    Route::get('/events', [EventController::class, 'index'])->name('events.index')->middleware(['permission:view events']);
    Route::get('/events/create', [EventController::class, 'create'])->name('events.create')->middleware(['permission:view events']);
    Route::post('/events', [EventController::class, 'store'])->name('events.store')->middleware(['permission:create events']);
    Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show')->middleware(['permission:view events']);
    Route::get('/events/{event}/edit', [EventController::class, 'edit'])->name('events.edit')->middleware(['permission:view events']);
    Route::put('/events/{event}', [EventController::class, 'update'])->name('events.update')->middleware(['permission:edit events']);
    Route::delete('/events/{event}', [EventController::class, 'destroy'])->name('events.destroy')->middleware(['permission:delete events']);

    // Years
    Route::get('/years', [YearController::class, 'index'])->name('years.index')->middleware(['permission:view years']);
    Route::get('/years/create', [YearController::class, 'create'])->name('years.create')->middleware(['permission:view years']);
    Route::post('/years', [YearController::class, 'store'])->name('years.store')->middleware(['permission:create years']);
    Route::get('/years/{year}', [YearController::class, 'show'])->name('years.show')->middleware(['permission:view years']);
    Route::get('/years/{year}/edit', [YearController::class, 'edit'])->name('years.edit')->middleware(['permission:view years']);
    Route::put('/years/{year}', [YearController::class, 'update'])->name('years.update')->middleware(['permission:edit years']);
    Route::delete('/years/{year}', [YearController::class, 'destroy'])->name('years.destroy')->middleware(['permission:delete years']);

    // Buildings
    Route::get('/buildings', [BuildingController::class, 'index'])->name('buildings.index')->middleware(['permission:view buildings']);
    Route::get('/buildings/create', [BuildingController::class, 'create'])->name('buildings.create')->middleware(['permission:view buildings']);
    Route::post('/buildings', [BuildingController::class, 'store'])->name('buildings.store')->middleware(['permission:create buildings']);
    Route::get('/buildings/{building}', [BuildingController::class, 'show'])->name('buildings.show')->middleware(['permission:view buildings']);
    Route::get('/buildings/{building}/edit', [BuildingController::class, 'edit'])->name('buildings.edit')->middleware(['permission:view buildings']);
    Route::put('/buildings/{building}', [BuildingController::class, 'update'])->name('buildings.update')->middleware(['permission:edit buildings']);
    Route::delete('/buildings/{building}', [BuildingController::class, 'destroy'])->name('buildings.destroy')->middleware(['permission:delete buildings']);

    // Programs
    Route::get('/programs/export', [ProgramController::class, 'export'])->name('programs.export')->middleware(['permission:export programs']);
    Route::post('/programs/import', [ProgramController::class, 'import'])->name('programs.import')->middleware(['permission:import programs']);
    Route::get('/programs', [ProgramController::class, 'index'])->name('programs.index')->middleware(['permission:view programs']);
    Route::get('/programs/create', [ProgramController::class, 'create'])->name('programs.create')->middleware(['permission:view programs']);
    Route::post('/programs', [ProgramController::class, 'store'])->name('programs.store')->middleware(['permission:create programs']);
    Route::get('/programs/{program}', [ProgramController::class, 'show'])->name('programs.show')->middleware(['permission:view programs']);
    Route::get('/programs/{program}/edit', [ProgramController::class, 'edit'])->name('programs.edit')->middleware(['permission:view programs']);
    Route::put('/programs/{program}', [ProgramController::class, 'update'])->name('programs.update')->middleware(['permission:edit programs']);
    Route::delete('/programs/{program}', [ProgramController::class, 'destroy'])->name('programs.destroy')->middleware(['permission:delete programs']);

    // Venues

    Route::get('/venues/timetable/export', [TimetableController::class, 'exportVenueTimetable'])->name('venues.timetable.export');
    Route::get('/venues/timetable', [TimetableController::class, 'venuesTimetable'])->name('venues.timetable');
    Route::get('/venues/summary/pdf', [VenueController::class, 'summaryPdf'])->name('venues.summary.pdf');
    Route::get('/venues/summary', [VenueController::class, 'summary'])->name('venues.summary');
    Route::get('/venue-sessions/{venue}/pdf', [VenueController::class, 'sessionsPdf'])->name('venue.sessions.pdf');
    Route::get('/venue-sessions', [VenueController::class, 'sessionsIndex'])->name('venue.sessions.index');
    Route::get('/venue-sessions/{venue}', [VenueController::class, 'sessionsShow'])->name('venue.sessions.show');
    Route::get('venues/availability', [VenueController::class, 'availability'])->name('venues.availability');
    Route::post('/venues/import', [VenueController::class, 'import'])->name('venues.import')->middleware(['permission:import venues']);
    Route::get('/venues/export', [VenueController::class, 'exportVenues'])->name('venues.export')->middleware(['permission:export venues']);
    Route::get('/venues', [VenueController::class, 'index'])->name('venues.index')->middleware(['permission:view venues']);
    Route::get('/venues/create', [VenueController::class, 'create'])->name('venues.create')->middleware(['permission:view venues']);
    Route::post('/venues', [VenueController::class, 'store'])->name('venues.store')->middleware(['permission:create venues']);
    Route::get('/venues/{venue}', [VenueController::class, 'show'])->name('venues.show')->middleware(['permission:view venues']);
    Route::get('/venues/{venue}/edit', [VenueController::class, 'edit'])->name('venues.edit')->middleware(['permission:view venues']);
    Route::put('/venues/{venue}', [VenueController::class, 'update'])->name('venues.update')->middleware(['permission:edit venues']);
    Route::delete('/venues/{venue}', [VenueController::class, 'destroy'])->name('venues.destroy')->middleware(['permission:delete venues']);

    // Suggestions
    Route::get('/suggestions', [AdminSuggestionController::class, 'index'])->name('admin.suggestions.index')->middleware(['permission:view suggestions']);
    Route::get('/suggestions/conversation/{student_id}', [AdminSuggestionController::class, 'conversation'])->name('admin.suggestions.conversation')->middleware(['permission:view suggestions']);
    Route::post('/suggestions/reply/{student_id}', [AdminSuggestionController::class, 'replyToStudent'])->name('admin.suggestions.reply')->middleware(['permission:reply suggestions']);

    // Calendar
    Route::get('/calendar/export', [CalendarController::class, 'export'])->name('calendar.export')->middleware(['permission:export calendar']);
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index')->middleware(['permission:view calendar']);
    Route::get('/calendar/create', [CalendarController::class, 'create'])->name('calendar.create')->middleware(['permission:view calendar']);
    Route::post('/calendar', [CalendarController::class, 'store'])->name('calendar.store')->middleware(['permission:create calendar']);
    Route::get('/calendar/{calendar}', [CalendarController::class, 'show'])->name('calendar.show')->middleware(['permission:view calendar']);
    Route::get('/calendar/{calendar}/edit', [CalendarController::class, 'edit'])->name('calendar.edit')->middleware(['permission:view calendar']);
    Route::put('/calendar/{calendar}', [CalendarController::class, 'update'])->name('calendar.update')->middleware(['permission:edit calendar']);
    Route::delete('/calendar/{calendar}', [CalendarController::class, 'destroy'])->name('calendar.destroy')->middleware(['permission:delete calendar']);

    // IPT
    Route::post('/ipt/{ipt}/submit-report', [IptController::class, 'submitReport'])->name('ipt.submitReport')->middleware(['permission:submit ipt reports']);
    Route::get('/ipt/{ipt}/view-evaluations', [IptController::class, 'viewEvaluations'])->name('ipt.viewEvaluations')->middleware(['permission:view ipt evaluations']);
    Route::get('/ipt', [IptController::class, 'index'])->name('ipt.index')->middleware(['permission:view ipt']);
    Route::get('/ipt/create', [IptController::class, 'create'])->name('ipt.create')->middleware(['permission:view ipt']);
    Route::post('/ipt', [IptController::class, 'store'])->name('ipt.store')->middleware(['permission:create ipt']);
    Route::get('/ipt/{ipt}', [IptController::class, 'show'])->name('ipt.show')->middleware(['permission:view ipt']);
    Route::get('/ipt/{ipt}/edit', [IptController::class, 'edit'])->name('ipt.edit')->middleware(['permission:view ipt']);
    Route::put('/ipt/{ipt}', [IptController::class, 'update'])->name('ipt.update')->middleware(['permission:edit ipt']);
    Route::delete('/ipt/{ipt}', [IptController::class, 'destroy'])->name('ipt.destroy')->middleware(['permission:delete ipt']);

    // Alumni
    Route::get('/alumni', [AlumniController::class, 'index'])->name('alumni.index')->middleware(['permission:view alumni']);
    Route::get('/alumni/create', [AlumniController::class, 'create'])->name('alumni.create')->middleware(['permission:view alumni']);
    Route::post('/alumni', [AlumniController::class, 'store'])->name('alumni.store')->middleware(['permission:create alumni']);
    Route::get('/alumni/{alumnus}', [AlumniController::class, 'show'])->name('alumni.show')->middleware(['permission:view alumni']);
    Route::get('/alumni/{alumnus}/edit', [AlumniController::class, 'edit'])->name('alumni.edit')->middleware(['permission:view alumni']);
    Route::put('/alumni/{alumnus}', [AlumniController::class, 'update'])->name('alumni.update')->middleware(['permission:edit alumni']);
    Route::delete('/alumni/{alumnus}', [AlumniController::class, 'destroy'])->name('alumni.destroy')->middleware(['permission:delete alumni']);

    // Enrolled Courses
    Route::post('/enrolled-courses/{course}/enroll', [EnrolledCourseController::class, 'enroll'])->name('enrolled-courses.enroll')->middleware(['permission:enroll courses']);
    Route::delete('/enrolled-courses/{course}/drop', [EnrolledCourseController::class, 'drop'])->name('enrolled-courses.drop')->middleware(['permission:drop enrolled courses']);
    Route::get('/enrolled-courses', [EnrolledCourseController::class, 'index'])->name('enrolled-courses.index')->middleware(['permission:view enrolled courses']);
    Route::get('/enrolled-courses/create', [EnrolledCourseController::class, 'create'])->name('enrolled-courses.create')->middleware(['permission:view enrolled courses']);
    Route::post('/enrolled-courses', [EnrolledCourseController::class, 'store'])->name('enrolled-courses.store')->middleware(['permission:create enrolled courses']);
    Route::get('/enrolled-courses/{enrolled_course}', [EnrolledCourseController::class, 'show'])->name('enrolled-courses.show')->middleware(['permission:view enrolled courses']);
    Route::get('/enrolled-courses/{enrolled_course}/edit', [EnrolledCourseController::class, 'edit'])->name('enrolled-courses.edit')->middleware(['permission:view enrolled courses']);
    Route::put('/enrolled-courses/{enrolled_course}', [EnrolledCourseController::class, 'update'])->name('enrolled-courses.update')->middleware(['permission:edit enrolled courses']);
    Route::delete('/enrolled-courses/{enrolled_course}', [EnrolledCourseController::class, 'destroy'])->name('enrolled-courses.destroy')->middleware(['permission:delete enrolled courses']);
});

require __DIR__ . '/auth.php';
