<?php

namespace Hewerthomn\ErrorTracker\Http\Controllers;

use Hewerthomn\ErrorTracker\Models\Issue;
use Hewerthomn\ErrorTracker\Services\IssueStatusService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Carbon;

class IssueController extends Controller
{
    public function __construct(
        protected IssueStatusService $issueStatusService,
    ) {}

    public function show(Request $request, Issue $issue)
    {
        $issue->load([
            'lastEvent',
            'events' => fn ($query) => $query->latest('occurred_at')->limit(25),
            'notifications' => fn ($query) => $query->latest('sent_at')->limit(5),
            'trends' => fn ($query) => $query
                ->where('bucket_granularity', 'hour')
                ->orderBy('bucket_start'),
        ]);

        /** @var view-string $view */
        $view = 'error-tracker::dashboard.issue-show';

        return view($view, [
            'issue' => $issue,
            'trendLabels' => $issue->trends->pluck('bucket_start')->map(
                fn ($date) => $date->format('d/m H:i')
            )->values(),
            'trendValues' => $issue->trends->pluck('events_count')->values(),
        ]);
    }

    public function resolve(Issue $issue)
    {
        $this->issueStatusService->resolveManually($issue);

        return redirect()
            ->route('error-tracker.issues.show', $issue)
            ->with('status', 'Issue resolved.');
    }

    public function reopen(Issue $issue)
    {
        $this->issueStatusService->reopen($issue);

        return redirect()
            ->route('error-tracker.issues.show', $issue)
            ->with('status', 'Issue reopened.');
    }

    public function ignore(Issue $issue)
    {
        $this->issueStatusService->ignore($issue);

        return redirect()
            ->route('error-tracker.issues.show', $issue)
            ->with('status', 'Issue ignored.');
    }

    public function mute(Request $request, Issue $issue)
    {
        $data = $request->validate([
            'muted_until' => ['nullable', 'date'],
            'mute_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $mutedUntil = filled($data['muted_until'] ?? null)
            ? Carbon::parse($data['muted_until'])
            : null;

        $muteReason = filled($data['mute_reason'] ?? null)
            ? trim($data['mute_reason'])
            : null;

        $this->issueStatusService->mute($issue, $mutedUntil, $muteReason);

        return redirect()
            ->route('error-tracker.issues.show', $issue)
            ->with('status', 'Issue muted.');
    }

    public function unmute(Issue $issue)
    {
        $this->issueStatusService->unmute($issue);

        return redirect()
            ->route('error-tracker.issues.show', $issue)
            ->with('status', 'Issue unmuted.');
    }
}
