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
        ->and($feedback->user_id)->toBeNull()
        ->and($feedback->name)->toBe('Everton')
        ->and($feedback->email)->toBe('everton@example.test')
        ->and($feedback->message)->toBe('I clicked save and the page crashed.')
        ->and($feedback->url)->toBe('http://localhost/test-page');
});

it('stores authenticated feedback using the signed in user identity', function () {
    config()->set('error-tracker.feedback.enabled', true);
    config()->set('error-tracker.feedback.only_production', false);

    $user = new User([
        'id' => 42,
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
        ->and($feedback->event_id)->toBe($result->event->id)
        ->and($feedback->user_id)->toBe('42')
        ->and($feedback->name)->toBe('Real User')
        ->and($feedback->email)->toBe('real@example.test')
        ->and($feedback->message)->toBe('The page failed after submit.');
});

it('ignores submitted identity fields for authenticated feedback', function () {
    config()->set('error-tracker.feedback.enabled', true);
    config()->set('error-tracker.feedback.only_production', false);
    config()->set('error-tracker.feedback.prefill_authenticated_user', false);

    $user = new User([
        'id' => 123,
        'name' => 'Trusted User',
        'email' => 'trusted@example.test',
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
            'name' => 'Submitted Name',
            'email' => 'not-an-email',
            'message' => 'The page failed while I was saving.',
            'page_url' => 'http://localhost/authenticated-page',
        ]
    );

    $response->assertOk();

    $feedback = Feedback::query()->first();

    expect($feedback)->not->toBeNull()
        ->and($feedback->user_id)->toBe('123')
        ->and($feedback->name)->toBe('Trusted User')
        ->and($feedback->email)->toBe('trusted@example.test');
});

it('stores feedback against the event occurrence instead of only the issue', function () {
    config()->set('error-tracker.feedback.enabled', true);
    config()->set('error-tracker.feedback.only_production', false);

    $action = app(RecordThrowableAction::class);
    $first = recordFeedbackException($action);
    $second = recordFeedbackException($action);

    expect($first)->not->toBeNull()
        ->and($second)->not->toBeNull()
        ->and($first->issue->id)->toBe($second->issue->id)
        ->and($first->event->id)->not->toBe($second->event->id);

    /** @var TestCase $this */
    $this->post(route('error-tracker.feedback.store', $first->event->feedback_token), [
        'message' => 'First event context.',
    ])->assertOk();

    $this->post(route('error-tracker.feedback.store', $second->event->feedback_token), [
        'message' => 'Second event context.',
    ])->assertOk();

    expect(Feedback::query()->count())->toBe(2)
        ->and(Feedback::query()->where('event_id', $first->event->id)->value('message'))->toBe('First event context.')
        ->and(Feedback::query()->where('event_id', $second->event->id)->value('message'))->toBe('Second event context.');
});

it('rejects guest feedback when guest feedback is disabled', function () {
    config()->set('error-tracker.feedback.enabled', true);
    config()->set('error-tracker.feedback.only_production', false);
    config()->set('error-tracker.feedback.allow_guest', false);

    $result = app(RecordThrowableAction::class)->handle(makeFeedbackException(), [
        'level' => 'error',
    ]);

    expect($result)->not->toBeNull();

    /** @var TestCase $this */
    $this->post(route('error-tracker.feedback.store', $result->event->feedback_token), [
        'message' => 'Guest context.',
    ])->assertForbidden();

    expect(Feedback::query()->count())->toBe(0);
});

it('renders authenticated feedback identity fields as readonly', function () {
    $user = new User([
        'id' => 42,
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
        ->toContain('Signed-in user information is attached automatically and cannot be changed here.');
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

it('does not render stack trace details on the public error page', function () {
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

    expect(str_contains($html, 'Feedback test exception'))->toBeFalse();
    expect(str_contains($html, 'Stack trace'))->toBeFalse();
    expect(str_contains($html, 'makeFeedbackException'))->toBeFalse();
});

function makeFeedbackException(): Throwable
{
    try {
        throw new RuntimeException('Feedback test exception');
    } catch (Throwable $e) {
        return $e;
    }
}

function recordFeedbackException(RecordThrowableAction $action)
{
    return $action->handle(makeFeedbackException(), [
        'level' => 'error',
    ]);
}
