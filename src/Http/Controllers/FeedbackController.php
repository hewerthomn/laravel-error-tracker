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
        $input = $this->feedbackInput($request);

        $validator = Validator::make($input, [
            'name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'message' => ['required', 'string', 'max:'.(int) config('error-tracker.feedback.max_length', 5000)],
            'page_url' => ['nullable', 'string', 'max:2048'],
        ]);

        if ($validator->fails()) {
            /** @var view-string $view */
            $view = 'error-tracker::error.exception';

            return response()->view($view, [
                'title' => config('error-tracker.error_page.title'),
                'message' => config('error-tracker.error_page.message'),
                'showReference' => config('error-tracker.error_page.show_reference', true),
                'reference' => $event->uuid,
                'event' => $event,
                'issue' => $event->issue,
                'showFeedbackForm' => true,
                'feedbackErrors' => $validator->errors(),
                'oldInput' => $input,
                'collectName' => config('error-tracker.feedback.collect_name', true),
                'collectEmail' => config('error-tracker.feedback.collect_email', true),
                'pageUrl' => $input['page_url'] ?? null,
                ...$this->feedbackUserViewData($request),
            ], 422);
        }

        $this->feedbackService->submit($token, $validator->validated(), $request);

        /** @var view-string $view */
        $view = 'error-tracker::error.feedback-submitted';

        return response()->view($view, [
            'title' => 'Feedback submitted',
            'message' => 'Thank you. Your feedback has been recorded.',
        ]);
    }

    protected function feedbackInput(Request $request): array
    {
        $input = $request->all();
        $user = $request->user();

        if ($user && config('error-tracker.feedback.prefill_authenticated_user', true)) {
            $input['name'] = data_get($user, 'name');
            $input['email'] = data_get($user, 'email');
        }

        return $input;
    }

    protected function feedbackUserViewData(Request $request): array
    {
        $user = $request->user();
        $prefillAuthenticatedUser = config('error-tracker.feedback.prefill_authenticated_user', true);
        $isFeedbackUserAuthenticated = (bool) $user;

        return [
            'feedbackUser' => $user,
            'authenticatedUser' => $user,
            'feedbackName' => $prefillAuthenticatedUser && $user ? data_get($user, 'name') : null,
            'feedbackEmail' => $prefillAuthenticatedUser && $user ? data_get($user, 'email') : null,
            'isFeedbackUserAuthenticated' => $isFeedbackUserAuthenticated,
            'lockAuthenticatedUserFields' => $isFeedbackUserAuthenticated
                && config('error-tracker.feedback.lock_authenticated_user_fields', true),
        ];
    }
}
