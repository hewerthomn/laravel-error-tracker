<?php

use Hewerthomn\ErrorTracker\Actions\RecordThrowableAction;
use Hewerthomn\ErrorTracker\Models\Feedback;
use Hewerthomn\ErrorTracker\Tests\Fixtures\User;
use Hewerthomn\ErrorTracker\Tests\TestCase;

it('stores guest feedback using the submitted identity fields', function () {
    config()->set('error-tracker.feedback.enabled', true);
    config()->set('error-tracker.feedback.only_production', false);

    $result = app(RecordThrowableAction::class)->handle(makeFeedbackException(), [
        'level' => 'error',
    ]);

    expect($result)->not->toBeNull()
        ->and($result->event->feedback_token)->not->toBeEmpty();

    /** @var TestCase $this */
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

it('stores authenticated feedback using the signed in user identity', function () {
    config()->set('error-tracker.feedback.enabled', true);
    config()->set('error-tracker.feedback.only_production', false);

    $user = new User([
        'name' => 'Real User',
        'email' => 'real@example.test',
    ]);

    /** @var TestCase $this */
    $this->actingAs($user);

    $result = app(RecordThrowableAction::class)->handle(makeFeedbackException(), [
        'level' => 'error',
    ]);

    expect($result)->not->toBeNull();

    $response = $this->post(
        route('error-tracker.feedback.store', $result->event->feedback_token),
        [
            'name' => 'Fake Name',
            'email' => 'fake@example.test',
            'message' => 'The page failed after submit.',
            'page_url' => 'http://localhost/authenticated-page',
        ]
    );

    $response->assertOk();

    $feedback = Feedback::query()->first();

    expect($feedback)->not->toBeNull()
        ->and($feedback->name)->toBe('Real User')
        ->and($feedback->email)->toBe('real@example.test')
        ->and($feedback->message)->toBe('The page failed after submit.');
});

it('renders authenticated feedback identity fields as readonly', function () {
    $user = new User([
        'name' => 'Real User',
        'email' => 'real@example.test',
    ]);

    /** @var TestCase $this */
    $this->actingAs($user);

    $result = app(RecordThrowableAction::class)->handle(makeFeedbackException(), [
        'level' => 'error',
    ]);

    expect($result)->not->toBeNull();

    /** @var view-string $view */
    $view = 'error-tracker::error.exception';

    $html = view($view, [
        'title' => 'Something went wrong',
        'message' => 'An unexpected error occurred.',
        'showReference' => true,
        'reference' => $result->event->uuid,
        'event' => $result->event,
        'issue' => $result->issue,
        'showFeedbackForm' => true,
        'collectName' => true,
        'collectEmail' => true,
        'feedbackName' => data_get($user, 'name'),
        'feedbackEmail' => data_get($user, 'email'),
        'isFeedbackUserAuthenticated' => true,
        'lockAuthenticatedUserFields' => true,
        'pageUrl' => 'http://localhost/error-page',
    ])->render();

    expect($html)
        ->toContain('value="Real User"')
        ->toContain('value="real@example.test"')
        ->toContain('readonly')
        ->toContain('Signed-in user information is attached automatically.');
});

it('renders guest feedback identity fields as editable', function () {
    $result = app(RecordThrowableAction::class)->handle(makeFeedbackException(), [
        'level' => 'error',
    ]);

    expect($result)->not->toBeNull();

    /** @var view-string $view */
    $view = 'error-tracker::error.exception';

    $html = view($view, [
        'title' => 'Something went wrong',
        'message' => 'An unexpected error occurred.',
        'showReference' => true,
        'reference' => $result->event->uuid,
        'event' => $result->event,
        'issue' => $result->issue,
        'showFeedbackForm' => true,
        'collectName' => true,
        'collectEmail' => true,
        'isFeedbackUserAuthenticated' => false,
        'lockAuthenticatedUserFields' => false,
        'pageUrl' => 'http://localhost/error-page',
    ])->render();

    preg_match('/<input[^>]+name="name"[^>]*>/', $html, $nameMatches);
    preg_match('/<input[^>]+name="email"[^>]*>/', $html, $emailMatches);

    expect($nameMatches[0] ?? '')
        ->not->toContain('readonly')
        ->and($emailMatches[0] ?? '')
        ->not->toContain('readonly')
        ->and($html)
        ->not->toContain('Signed-in user information is attached automatically.');
});

function makeFeedbackException(): Throwable
{
    try {
        throw new RuntimeException('Feedback test exception');
    } catch (Throwable $e) {
        return $e;
    }
}
