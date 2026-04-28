<?php

namespace Hewerthomn\ErrorTracker\Notifications;

use Hewerthomn\ErrorTracker\Models\Issue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\SlackMessage;

class IssueTriggeredNotification extends Notification
{
    public function __construct(
        protected Issue $issue,
        protected string $trigger,
        protected array $channels,
    ) {}

    public function via(object $notifiable): array
    {
        return $this->channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subjectLine())
            ->line($this->subjectLine())
            ->line('Environment: '.$this->issue->environment)
            ->line('Status: '.$this->issue->status)
            ->line('Total events: '.number_format((int) $this->issue->total_events))
            ->line('Last seen: '.optional($this->issue->last_seen_at)?->format('Y-m-d H:i:s'))
            ->action('View issue', $this->issueUrl());
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->text($this->subjectLine())
            ->sectionBlock(function ($block) {
                $block->text(
                    "*Issue:* {$this->issue->title}\n".
                    "*Environment:* {$this->issue->environment}\n".
                    "*Status:* {$this->issue->status}\n".
                    '*Total events:* '.number_format((int) $this->issue->total_events)."\n".
                    "*URL:* {$this->issueUrl()}"
                )->markdown();
            });
    }

    protected function subjectLine(): string
    {
        return match ($this->trigger) {
            'new_issue' => 'New error tracker issue detected',
            'regression' => 'Error tracker issue regressed',
            'reactivated' => 'Resolved issue has been reactivated',
            default => 'Error tracker issue notification',
        };
    }

    protected function issueUrl(): string
    {
        return route('error-tracker.issues.show', $this->issue);
    }
}
