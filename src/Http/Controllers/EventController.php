<?php

namespace Hewerthomn\ErrorTracker\Http\Controllers;

use Hewerthomn\ErrorTracker\Models\Event;
use Hewerthomn\ErrorTracker\Support\StackTracePresenter;
use Illuminate\Routing\Controller;

class EventController extends Controller
{
    public function show(Event $event, StackTracePresenter $stackTracePresenter)
    {
        $event->load(['issue', 'feedback']);

        /** @var view-string $view */
        $view = 'error-tracker::dashboard.event-show';

        return view($view, [
            'event' => $event,
            'stackTrace' => $stackTracePresenter->present($event->trace_json),
        ]);
    }
}
