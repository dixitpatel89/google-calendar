<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google_Client;
use Google_Service_Calendar;
use Google_Service_Calendar_Event;

class GoogleCalenderController extends Controller
{
    private $client;

    public function __construct()
    {
        $this->client = new Google_Client();
        $this->client->setAuthConfig(storage_path('app/public/apps.googleusercontent.com.json'));
        $this->client->addScope(Google_Service_Calendar::CALENDAR);
        $this->client->setAccessType('offline');
        $this->client->setApprovalPrompt('force');
        $this->client->setRedirectUri(route('oauth2callback'));
    }

    public function connect()
    {
        $authUrl = $this->client->createAuthUrl();
        return redirect($authUrl);
    }

    public function oauth2callback(Request $request)
    {
        $code = $request->input('code');
        if ($code) {
            $this->client->authenticate($code);
            $token = $this->client->getAccessToken();
            $request->session()->put('google_calendar_token', $token);
            return redirect()->route('calendar.list');
        }
        return redirect()->route('connect.google')->with('error', 'Failed to authenticate with Google.');
    }

    public function listCalendars(Request $request)
    {
        $token = $request->session()->get('google_calendar_token');

        if (!$token) {
            return redirect()->route('connect.google');
        }

        $this->client->setAccessToken($token);

        if ($this->client->isAccessTokenExpired()) {
            $refreshToken = $this->client->getRefreshToken();
            if ($refreshToken) {
                $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                $request->session()->put('google_calendar_token', $this->client->getAccessToken());
            } else {
                return redirect()->route('connect.google')->with('error', 'Session expired. Please reconnect.');
            }
        }

        $service = new Google_Service_Calendar($this->client);
        $calendarList = $service->calendarList->listCalendarList();

        return response()->json($calendarList->getItems());
    }

    public function getEvents(Request $request, $calendarId)
    {
        $token = $request->session()->get('google_calendar_token');

        if (!$token) {
            return redirect()->route('connect.google');
        }

        $this->client->setAccessToken($token);

        if ($this->client->isAccessTokenExpired()) {
            $refreshToken = $this->client->getRefreshToken();
            if ($refreshToken) {
                $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                $request->session()->put('google_calendar_token', $this->client->getAccessToken());
            } else {
                return redirect()->route('connect.google')->with('error', 'Session expired. Please reconnect.');
            }
        }

        $service = new Google_Service_Calendar($this->client);
        $events = $service->events->listEvents($calendarId);

        return response()->json($events->getItems());
    }

    public function createEvent(Request $request)
    {
        $calendarId = env('GOOGLE_CALENDAR_ID');

        $token = $request->session()->get('google_calendar_token');
        if (!$token) {
            return redirect()->route('connect.google');
        }

        $this->client->setAccessToken($token);

        if ($this->client->isAccessTokenExpired()) {
            $refreshToken = $this->client->getRefreshToken();
            if ($refreshToken) {
                $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
                $request->session()->put('google_calendar_token', $this->client->getAccessToken());
            } else {
                return redirect()->route('connect.google')->with('error', 'Session expired. Please reconnect.');
            }
        }

        $service = new Google_Service_Calendar($this->client);

        $event = new Google_Service_Calendar_Event([
            'summary' => $request->input('summary'),
            'location' => $request->input('location'),
            'description' => $request->input('description'),
            'start' => [
                'dateTime' => $request->input('start'),
                'timeZone' => 'America/Los_Angeles',
            ],
            'end' => [
                'dateTime' => $request->input('end'),
                'timeZone' => 'America/Los_Angeles',
            ],
            'attendees' => array_map(function ($email) {
                return ['email' => $email];
            }, $request->input('attendees', [])),
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'email', 'minutes' => 24 * 60],
                    ['method' => 'popup', 'minutes' => 10],
                ],
            ],
        ]);

        $event = $service->events->insert($calendarId, $event);

        return response()->json($event);
    }
}
