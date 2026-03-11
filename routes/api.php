<?php
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DegreeController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\EventRegistrationController;
use App\Http\Controllers\Api\EventSessionController;
use App\Http\Controllers\Api\EventSpeakerController;
use App\Http\Controllers\Api\TestimonialController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

// ── Auth ─────────────────────────────────────────────────────────────────────
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// ── Public testimonials ────────────────────────────────────────────────────────
Route::get('/testimonials', [TestimonialController::class, 'index']);

// ── Public events ─────────────────────────────────────────────────────────────
Route::get('/events',        [EventController::class, 'index']);
Route::get('/events/{slug}', [EventController::class, 'show']);
Route::post('/events/{slug}/register', [EventRegistrationController::class, 'store'])->middleware('throttle:10,1');

// ── Authenticated ─────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    // Profile
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);
    Route::patch('/me',    [AuthController::class, 'updateProfile']);

    // My data
    Route::get('/my-registrations', [EventRegistrationController::class, 'myRegistrations']);

    // Events CRUD
    Route::post('/events',                      [EventController::class, 'store']);
    Route::patch('/events/{event}',             [EventController::class, 'update']);
    Route::post('/events/{event}/image',        [EventController::class, 'uploadImage']);
    Route::delete('/events/{event}',            [EventController::class, 'destroy']);

    // Sessions
    Route::post('/events/{event}/sessions',          [EventSessionController::class, 'store']);
    Route::patch('/event-sessions/{session}',        [EventSessionController::class, 'update']);
    Route::delete('/event-sessions/{session}',       [EventSessionController::class, 'destroy']);
    Route::post('/event-sessions/{session}/items',   [EventSessionController::class, 'storeItem']);
    Route::delete('/event-session-items/{item}',     [EventSessionController::class, 'destroyItem']);

    // Speakers
    Route::post('/events/{slug}/speakers',       [EventSpeakerController::class, 'store']);
    Route::patch('/speakers/{speaker}',          [EventSpeakerController::class, 'update']);
    Route::post('/speakers/{speaker}/image',     [EventSpeakerController::class, 'uploadImage']);
    Route::delete('/speakers/{speaker}',         [EventSpeakerController::class, 'destroy']);

    // Doctors pool
    Route::get('/doctors',                   [DoctorController::class, 'index']);
    Route::post('/doctors',                  [DoctorController::class, 'store']);
    Route::patch('/doctors/{doctor}',        [DoctorController::class, 'update']);
    Route::post('/doctors/{doctor}/image',   [DoctorController::class, 'uploadImage']);
    Route::delete('/doctors/{doctor}',       [DoctorController::class, 'destroy']);

    // Registrations management
    Route::get('/events/{slug}/registrations',                      [EventRegistrationController::class, 'index']);
    Route::patch('/registrations/{registration}/status',            [EventRegistrationController::class, 'updateStatus']);
    Route::delete('/registrations/{registration}',                  [EventRegistrationController::class, 'cancel']);
    Route::delete('/registrations/{registration}/force',            [EventRegistrationController::class, 'forceDelete']);

    // Testimonials
    Route::post('/testimonials',                        [TestimonialController::class, 'store']);
    Route::patch('/testimonials/{testimonial}',         [TestimonialController::class, 'update']);
    Route::post('/testimonials/{testimonial}/image',    [TestimonialController::class, 'uploadImage']);
    Route::delete('/testimonials/{testimonial}',        [TestimonialController::class, 'destroy']);

    // Users
    Route::get('/users',                 [UserController::class, 'index']);
    Route::get('/users/{user}',          [UserController::class, 'show']);
    Route::patch('/users/{user}/role',   [UserController::class, 'updateRole']);
    Route::delete('/users/{user}',       [UserController::class, 'destroy']);

    // Degrees
    Route::get('/users/{user}/degrees',       [DegreeController::class, 'index']);
    Route::post('/users/{user}/degrees',      [DegreeController::class, 'store']);
    Route::get('/degrees/{degree}/download',  [DegreeController::class, 'download']);
    Route::delete('/degrees/{degree}',        [DegreeController::class, 'destroy']);
});
