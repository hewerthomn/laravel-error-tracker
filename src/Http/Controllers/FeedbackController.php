<?php

namespace Hewerthomn\ErrorTracker\Http\Controllers;

use Hewerthomn\ErrorTracker\Services\FeedbackService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;

class FeedbackController extends Controller
{
    public function __construct(
        protected FeedbackService $feedbackService,
    ) {}

    public function store(Request $request, string $token)
    {
        if (! config('error-tracker.feedback.enabled', false)) {
            abort(404);
        }

        if (
            config('error-tracker.feedback.only_production', false) &&
            ! app()->environment('production')
        ) {
            abort(404);
        }

        if (
            ! config('error-tracker.feedback.allow_guest', true) &&
            ! $request->user()
        ) {
            abort(403);
        }

        $event = $this->feedbackService->findEventByToken($token);

        $validator = Validator::make($request->all(), [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:'.(int) config('error-tracker.feedback.max_length', 5000)],
            'page_url' => ['nullable', 'string', 'max:2048'],
        ]);

        if ($validator->fails()) {
            return response()->view('error-tracker::error.exception', [
                'title' => config('error-tracker.error_page.title'),
                'message' => config('error-tracker.error_page.message'),
                'showReference' => config('error-tracker.error_page.show_reference', true),
                'reference' => $event->uuid,
                'event' => $event,
                'issue' => $event->issue,
                'showFeedbackForm' => true,
                'feedbackErrors' => $validator->errors(),
                'oldInput' => $request->all(),
                'collectName' => config('error-tracker.feedback.collect_name', true),
                'collectEmail' => config('error-tracker.feedback.collect_email', true),
                'pageUrl' => $request->input('page_url'),
            ], 422);
        }

        $this->feedbackService->submit($token, $validator->validated(), $request);

        return response()->view('error-tracker::error.feedback-submitted', [
            'title' => 'Feedback submitted',
            'message' => 'Thank you. Your feedback has been recorded.',
        ]);
    }
}
