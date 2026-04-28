<?php

namespace Hewerthomn\ErrorTracker\Support\Diagnostics;

class ConfigurationPresenter
{
    public function __construct(
        protected SecretMasker $secretMasker,
        protected DiagnosticsRunner $diagnostics,
    ) {}

    /**
     * @return array{sections: array<int, array{title: string, rows: array<int, array{label: string, value: mixed, type: string, status?: string}>}>, health_checks: array<int, array{key: string, label: string, status: string, target: string, description: string, fix_command: string|null, required: bool, feature: string|null, tone: string, ok: bool, detail: string}>, scheduler_hints: array<int, string>}
     */
    public function present(): array
    {
        return [
            'sections' => [
                $this->section('General', [
                    $this->row('Package enabled', config('error-tracker.enabled', true), 'boolean'),
                    $this->row('Dashboard path', '/'.trim((string) config('error-tracker.route.path', 'error-tracker'), '/')),
                    $this->row('Middleware', $this->listValue(config('error-tracker.route.middleware', ['web'])), 'list'),
                    $this->row('Gate', config('error-tracker.route.gate', 'viewErrorTracker')),
                    $this->row('App environment', app()->environment()),
                    $this->row('App debug', config('app.debug', false), 'boolean'),
                    $this->row('Config cached', $this->configurationIsCached(), 'boolean'),
                ]),
                $this->section('Capture', [
                    $this->row('Environments', $this->listValue(config('error-tracker.capture.environments', [])), 'list'),
                    $this->row('Levels', $this->listValue(config('error-tracker.capture.levels', ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'])), 'list'),
                    $this->row('Sample rate', config('error-tracker.capture.sample_rate', 1.0)),
                    $this->row('Store request body', config('error-tracker.capture.store_request_body', false), 'boolean'),
                    $this->row('Store headers', config('error-tracker.capture.store_headers', true), 'boolean'),
                    $this->row('Store user', config('error-tracker.capture.store_user', true), 'boolean'),
                    $this->row('Hash IP', config('error-tracker.capture.hash_ip', true), 'boolean'),
                    $this->row('Max trace frames', config('error-tracker.capture.max_trace_frames', 50)),
                ]),
                $this->section('Feedback', [
                    $this->row('Enabled', config('error-tracker.feedback.enabled', false), 'boolean'),
                    $this->row('Only HTML requests', config('error-tracker.feedback.only_html_requests', true), 'boolean'),
                    $this->row('Only production', config('error-tracker.feedback.only_production', false), 'boolean'),
                    $this->row('Allow guest', config('error-tracker.feedback.allow_guest', true), 'boolean'),
                    $this->row('Rate limit', config('error-tracker.feedback.rate_limit', '5,1')),
                ]),
                $this->section('Auto Resolve', [
                    $this->row('Enabled', config('error-tracker.auto_resolve.enabled', false), 'boolean'),
                    $this->row('After days', config('error-tracker.auto_resolve.after_days', 14)),
                    $this->row('Statuses', $this->listValue(config('error-tracker.auto_resolve.statuses', [])), 'list'),
                    $this->row('Levels', $this->listValue(config('error-tracker.auto_resolve.levels', [])), 'list'),
                    $this->row('Environments', $this->nullableListValue(config('error-tracker.auto_resolve.environments')), 'list'),
                    $this->row('Reason', config('error-tracker.auto_resolve.reason', '')),
                    $this->row('Command hint', 'php artisan error-tracker:auto-resolve', 'code'),
                ]),
                $this->section('Stack Trace', [
                    $this->row('Smart grouping', config('error-tracker.stacktrace.smart_grouping', true), 'boolean'),
                    $this->row('Path display', config('error-tracker.stacktrace.path_display', 'relative')),
                    $this->row('Store absolute paths', config('error-tracker.stacktrace.store_absolute_paths', false), 'boolean'),
                    $this->row('Project paths', $this->listValue(config('error-tracker.stacktrace.project_paths', [])), 'list'),
                    $this->row('Project namespaces', $this->listValue(config('error-tracker.stacktrace.project_namespaces', [])), 'list'),
                    $this->row('Collapse non-project frames', config('error-tracker.stacktrace.collapse_non_project_frames', true), 'boolean'),
                    $this->row('Show source context', config('error-tracker.stacktrace.show_source_context', true), 'boolean'),
                    $this->row('Source context lines', config('error-tracker.stacktrace.source_context_lines', 5)),
                    $this->row('Source context enabled', config('error-tracker.stacktrace.source_context.enabled', true), 'boolean'),
                    $this->row('Store arguments', config('error-tracker.stacktrace.store_arguments', false), 'boolean'),
                ]),
                $this->section('Notifications', [
                    $this->row('Enabled', config('error-tracker.notifications.enabled', true), 'boolean'),
                    $this->row('Channels', $this->listValue(config('error-tracker.notifications.channels', [])), 'list'),
                    $this->row('Notify on new issue', config('error-tracker.notifications.notify_on_new_issue', true), 'boolean'),
                    $this->row('Notify on regression', config('error-tracker.notifications.notify_on_regression', false), 'boolean'),
                    $this->row('Notify on reactivated', config('error-tracker.notifications.notify_on_reactivated', true), 'boolean'),
                    $this->row('Cooldown minutes', config('error-tracker.notifications.cooldown_minutes', 30)),
                    $this->row('Max per issue per hour', config('error-tracker.notifications.max_per_issue_per_hour', 3)),
                    $this->configuredRow('Mail recipient', config('error-tracker.notifications.mail_to')),
                    $this->configuredRow('Slack webhook', config('error-tracker.notifications.slack_webhook_url', config('error-tracker.notifications.slack_channel'))),
                ]),
                $this->section('Retention', [
                    $this->row('Events days', config('error-tracker.retention.events_days', 30)),
                    $this->row('Resolved issues days', config('error-tracker.retention.resolved_issues_days', 90)),
                    $this->row('Feedback days', config('error-tracker.retention.delete_feedback_after_days', 90)),
                    $this->row('Command hint', 'php artisan error-tracker:prune', 'code'),
                ]),
                $this->section('Redaction', [
                    $this->row('Headers list', $this->listValue(config('error-tracker.redaction.headers', [])), 'list'),
                    $this->row('Request fields list', $this->listValue(config('error-tracker.redaction.request_fields', [])), 'list'),
                ]),
            ],
            'health_checks' => $this->healthChecks(),
            'scheduler_hints' => [
                "Schedule::command('error-tracker:auto-resolve')->daily();",
                "Schedule::command('error-tracker:prune')->daily();",
            ],
        ];
    }

    /**
     * @param  array<int, array{label: string, value: mixed, type: string, status?: string}>  $rows
     * @return array{title: string, rows: array<int, array{label: string, value: mixed, type: string, status?: string}>}
     */
    protected function section(string $title, array $rows): array
    {
        return [
            'title' => $title,
            'rows' => $rows,
        ];
    }

    /**
     * @return array{label: string, value: mixed, type: string}
     */
    protected function row(string $label, mixed $value, string $type = 'text'): array
    {
        return [
            'label' => $label,
            'value' => $this->secretMasker->mask($label, $value),
            'type' => $type,
        ];
    }

    /**
     * @return array{label: string, value: string, type: string, status: string}
     */
    protected function configuredRow(string $label, mixed $value): array
    {
        $status = $this->secretMasker->status($value);

        return [
            'label' => $label,
            'value' => $status,
            'type' => 'status',
            'status' => $status,
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function listValue(mixed $value): array
    {
        if ($value === null) {
            return [];
        }

        if (! is_array($value)) {
            return [(string) $value];
        }

        return collect($value)
            ->map(fn (mixed $item): string => is_scalar($item) ? (string) $item : (json_encode($item) ?: ''))
            ->filter(fn (string $item): bool => $item !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    protected function nullableListValue(mixed $value): array
    {
        $list = $this->listValue($value);

        return $list === [] ? ['All environments'] : $list;
    }

    /**
     * @return array<int, array{key: string, label: string, status: string, target: string, description: string, fix_command: string|null, required: bool, feature: string|null, tone: string, ok: bool, detail: string}>
     */
    protected function healthChecks(): array
    {
        return collect($this->diagnostics->run())
            ->map(fn (DiagnosticCheck $check): array => $check->toArray())
            ->values()
            ->all();
    }

    protected function configurationIsCached(): bool
    {
        return app()->configurationIsCached();
    }
}
