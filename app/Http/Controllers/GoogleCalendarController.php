<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Oauth2;
use Spatie\GoogleCalendar\Event;
use Carbon\Carbon;


class GoogleCalendarController extends Controller
{
    public function redirectToGoogle()
    {
        $client = new Google_Client();
        $client->setAuthConfig(storage_path('app/public/apps.googleusercontent.com.json'));
        $client->addScope(Google_Service_Calendar::CALENDAR_READONLY);
        $client->setRedirectUri(route('callback'));
        $client->setAccessType('offline');
        
        return redirect()->away($client->createAuthUrl());
    }

    public function handleGoogleCallback(Request $request)
    {
        $client = new Google_Client();
        $client->setAuthConfig(storage_path('app/public/apps.googleusercontent.com.json'));
        $client->setRedirectUri(route('callback'));
        $client->authenticate($request->get('code'));

        session(['google_access_token' => $client->getAccessToken()]);

        return redirect('/calendar');
    }

    public function listCalendarEvents()
    {
        $client = new Google_Client();
        $client->setAuthConfig(storage_path('app/public/apps.googleusercontent.com.json'));
        $client->setAccessToken(session('google_access_token'));

        if ($client->isAccessTokenExpired()) {
            $client->refreshToken($client->getRefreshToken());
            session(['google_access_token' => $client->getAccessToken()]);
        }

        $service = new Google_Service_Calendar($client);

        $calendarId = 'primary';
        $events = $service->events->listEvents($calendarId);

        return view('calendar', ['events' => $events->getItems()]);
    }

    public function showCalendar()
    {
        $client = new Google_Client();
        $client->setAuthConfig(storage_path('app/public/apps.googleusercontent.com.json'));
        $client->setAccessToken(session('google_access_token'));

        if ($client->isAccessTokenExpired()) {
            $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            session(['google_access_token' => $client->getAccessToken()]);
        }

        $service = new Google_Service_Calendar($client);

        $calendarId = 'primary';
        $events = $service->events->listEvents($calendarId);

        $eventList = [];
        foreach ($events->getItems() as $event) {
            $eventList[] = [
                'title' => $event->getSummary(),
                'start' => $event->getStart()->getDateTime() ?: $event->getStart()->getDate(),
                'end' => $event->getEnd()->getDateTime() ?: $event->getEnd()->getDate(),
            ];
        }

        return view('calendar', ['events' => $eventList]);
    }

    public function saveNewEvent()
    {
        $event = new Event();

        $event->name = 'A new event from Radha Krishna!!';
        $event->startDateTime = Carbon::now();
        $event->endDateTime = Carbon::now()->addHour();
        $event->save();
    }
}