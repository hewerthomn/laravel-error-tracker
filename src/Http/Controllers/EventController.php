<?php

namespace Hewerthomn\ErrorTracker\Http\Controllers;

use Hewerthomn\ErrorTracker\Models\Event;
use Illuminate\Routing\Controller;

class EventController extends Controller
{
    public function show(Event $event)
    {
        $event->load(['issue', 'feedback']);

        return view('error-tracker::dashboard.event-show', [
            'event' => $event,
        ]);
    }
}
