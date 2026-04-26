<?php

namespace Hewerthomn\ErrorTracker\Http\Controllers;

use Hewerthomn\ErrorTracker\Models\Event;
use Illuminate\Routing\Controller;

class EventController extends Controller
{
    public function show(Event $event)
    {
        $event->load(['issue', 'feedback']);

        /** @var view-string $view */
        $view = 'error-tracker::dashboard.event-show';

        return view($view, [
            'event' => $event,
        ]);
    }
}
