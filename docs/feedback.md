# Custom Error Page and User Feedback

When enabled, the package can render a custom HTML error page only when:

- `APP_DEBUG=false`
- the request expects HTML
- the response is a server error

The optional feedback form is linked to the recorded event through
`feedback_token`, so the feedback is associated with the issue occurrence that
triggered the page.

The MVP feedback UI is Blade with lightweight Tailwind and Alpine.js usage,
without Livewire.

Guest users can fill in name and email fields when guest feedback is allowed and
those fields are enabled.

Authenticated users see name and email prefilled from their signed-in account as
readonly fields. This is only a usability hint: the backend always uses
`request()->user()` as the source of truth when available and ignores submitted
name/email values for signed-in users.

## Optional custom error page with feedback

Register exception capture and the optional error page rendering in
`bootstrap/app.php`:

```php
use Hewerthomn\ErrorTracker\Actions\RecordThrowableAction;
use Hewerthomn\ErrorTracker\Support\ErrorPageState;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->dontReportDuplicates();

        $exceptions->report(function (\Throwable $e) {
            $result = app(RecordThrowableAction::class)->handle($e);

            if ($result) {
                app(ErrorPageState::class)->set($result);
            }
        });

        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! config('error-tracker.error_page.enabled', true)) {
                return null;
            }

            if (
                config('error-tracker.error_page.only_when_debug_disabled', true) &&
                config('app.debug')
            ) {
                return null;
            }

            if (
                config('error-tracker.error_page.only_html_requests', true) &&
                $request->expectsJson()
            ) {
                return null;
            }

            $status = method_exists($e, 'getStatusCode')
                ? (int) $e->getStatusCode()
                : 500;

            if ($status < 500) {
                return null;
            }

            $result = app(ErrorPageState::class)->get();
            $event = $result?->event;
            $issue = $result?->issue;
            $user = $request->user();

            $showFeedbackForm =
                config('error-tracker.feedback.enabled', false) &&
                $event?->feedback_token &&
                (
                    $user ||
                    config('error-tracker.feedback.allow_guest', true)
                ) &&
                (
                    ! config('error-tracker.feedback.only_production', false) ||
                    app()->environment('production')
                );

            $feedbackName = $user ? data_get($user, 'name') : null;
            $feedbackEmail = $user ? data_get($user, 'email') : null;
            $isFeedbackUserAuthenticated = (bool) $user;
            $lockAuthenticatedUserFields = $isFeedbackUserAuthenticated;

            return response()->view('error-tracker::error.exception', [
                'title' => config('error-tracker.error_page.title', 'Something went wrong'),
                'message' => config(
                    'error-tracker.error_page.message',
                    'An unexpected error occurred. Please try again in a moment.'
                ),
                'showReference' => config('error-tracker.error_page.show_reference', true),
                'reference' => $event?->uuid,
                'event' => $event,
                'issue' => $issue,
                'showFeedbackForm' => $showFeedbackForm,
                'collectName' => config('error-tracker.feedback.collect_name', true),
                'collectEmail' => config('error-tracker.feedback.collect_email', true),
                'authenticatedUser' => $user,
                'feedbackUser' => $user,
                'feedbackName' => $feedbackName,
                'feedbackEmail' => $feedbackEmail,
                'isFeedbackUserAuthenticated' => $isFeedbackUserAuthenticated,
                'lockAuthenticatedUserFields' => $lockAuthenticatedUserFields,
                'pageUrl' => $request->fullUrl(),
            ], $status);
        });
    })
    ->create();
```
