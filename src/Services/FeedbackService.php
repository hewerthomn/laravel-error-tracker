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
        $userData = $this->resolveAuthenticatedUserData($request);

        if ($userData['authenticated']) {
            $data['name'] = $userData['name'];
            $data['email'] = $userData['email'];
        }

        return Feedback::query()->updateOrCreate(
            ['event_id' => $event->id],
            [
                'feedback_token' => $token,
                'user_id' => $userData['user_id'],
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'message' => $data['message'],
                'url' => $data['page_url'] ?? null,
                'user_agent' => (string) $request->userAgent(),
            ]
        );
    }

    /**
     * @return array{authenticated: bool, user_id: string|null, name: string|null, email: string|null}
     */
    protected function resolveAuthenticatedUserData(Request $request): array
    {
        $user = $request->user();

        if (! $user) {
            return [
                'authenticated' => false,
                'user_id' => null,
                'name' => null,
                'email' => null,
            ];
        }

        $authIdentifier = $user->getAuthIdentifier();

        return [
            'authenticated' => true,
            'user_id' => $authIdentifier !== null ? (string) $authIdentifier : null,
            'name' => $this->nullableString(data_get($user, 'name')),
            'email' => $this->nullableString(data_get($user, 'email')),
        ];
    }

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_scalar($value) ? (string) $value : null;
    }
}
