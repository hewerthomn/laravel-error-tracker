<?php

namespace Hewerthomn\ErrorTracker\Services;

use Hewerthomn\ErrorTracker\Models\Event;
use Hewerthomn\ErrorTracker\Models\Feedback;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class FeedbackService
{
    public function findEventByToken(string $token): Event
    {
        $event = Event::query()
            ->with('issue')
            ->where('feedback_token', $token)
            ->first();

        if (! $event) {
            throw new ModelNotFoundException('Event not found for feedback token.');
        }

        return $event;
    }

    public function submit(string $token, array $data, Request $request): Feedback
    {
        $event = $this->findEventByToken($token);
        $user = $request->user();

        if ($user && config('error-tracker.feedback.prefill_authenticated_user', true)) {
            $data['name'] = data_get($user, 'name');
            $data['email'] = data_get($user, 'email');
        }

        return Feedback::query()->updateOrCreate(
            ['event_id' => $event->id],
            [
                'feedback_token' => $token,
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'message' => $data['message'],
                'url' => $data['page_url'] ?? null,
                'user_agent' => (string) $request->userAgent(),
            ]
        );
    }
}
