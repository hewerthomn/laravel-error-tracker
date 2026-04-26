<?php

use Hewerthomn\ErrorTracker\Actions\RecordThrowableAction;
use Hewerthomn\ErrorTracker\Models\Feedback;

it('stores feedback for an event using the feedback token', function () {
    config()->set('error-tracker.feedback.enabled', true);
    config()->set('error-tracker.feedback.only_production', false);

    $result = app(RecordThrowableAction::class)->handle(makeFeedbackException(), [
        'level' => 'error',
    ]);

    expect($result)->not->toBeNull()
        ->and($result->event->feedback_token)->not->toBeEmpty();

    $response = $this->post(
        route('error-tracker.feedback.store', $result->event->feedback_token),
        [
            'name' => 'Everton',
            'email' => 'everton@example.test',
            'message' => 'I clicked save and the page crashed.',
            'page_url' => 'http://localhost/test-page',
        ]
    );

    $response->assertOk();

    expect(Feedback::query()->count())->toBe(1);

    $feedback = Feedback::query()->first();

    expect($feedback)->not->toBeNull()
        ->and($feedback->event_id)->toBe($result->event->id)
        ->and($feedback->feedback_token)->toBe($result->event->feedback_token)
        ->and($feedback->name)->toBe('Everton')
        ->and($feedback->email)->toBe('everton@example.test')
        ->and($feedback->message)->toBe('I clicked save and the page crashed.')
        ->and($feedback->url)->toBe('http://localhost/test-page');
});

function makeFeedbackException(): Throwable
{
    try {
        throw new RuntimeException('Feedback test exception');
    } catch (Throwable $e) {
        return $e;
    }
}
