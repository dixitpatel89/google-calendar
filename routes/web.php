<?php

use App\Http\Controllers\GoogleCalenderController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\GoogleCalendarController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/google', [GoogleCalendarController::class, 'redirectToGoogle'])->name('google.auth');
Route::get('/callback', [GoogleCalendarController::class, 'handleGoogleCallback'])->name('oauth2callback');
Route::get('/calendar', [GoogleCalendarController::class, 'listCalendarEvents']);
// Route::get('/calendar', [GoogleCalendarController::class, 'showCalendar'])->name('calendar');
// Route::get('/test', [GoogleCalendarController::class, 'saveNewEvent'])->name('saveNewEvent');
Route::get('/test', [GoogleCalenderController::class, 'createEvent'])->name('saveNewEvent');

Route::get('/connect/google', [GoogleCalenderController::class, 'connect'])->name('connect.google');
Route::get('/oauth2callback', [GoogleCalenderController::class, 'oauth2callback'])->name('oauth2callback');
Route::get('/calendar/list', [GoogleCalenderController::class, 'listCalendars'])->name('calendar.list');
Route::get('/calendar/events/{calendarId}', [GoogleCalenderController::class, 'getEvents'])->name('calendar.events');
