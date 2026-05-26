<?php

namespace Hewerthomn\ErrorTracker\Http\Controllers;

use Hewerthomn\ErrorTracker\Actions\CreateExternalIssueAction;
use Hewerthomn\ErrorTracker\Actions\LinkExternalIssueAction;
use Hewerthomn\ErrorTracker\Actions\SyncExternalIssueAction;
use Hewerthomn\ErrorTracker\Actions\UnlinkExternalIssueAction;
use Hewerthomn\ErrorTracker\Exceptions\ExternalIssueTrackerException;
use Hewerthomn\ErrorTracker\Models\ExternalIssue;
use Hewerthomn\ErrorTracker\Models\Issue;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ExternalIssueController extends Controller
{
    public function createGithub(Issue $issue, CreateExternalIssueAction $action)
    {
        try {
            $externalIssue = $action->execute($issue);

            return redirect()
                ->route('error-tracker.issues.show', $issue)
                ->with('status', 'GitHub issue #'.$externalIssue->external_number.' linked.');
        } catch (ExternalIssueTrackerException $exception) {
            return $this->failed($issue, $exception->getMessage());
        }
    }

    public function link(Request $request, Issue $issue, LinkExternalIssueAction $action)
    {
        $data = $request->validate([
            'external_issue' => ['required', 'string', 'max:255'],
        ]);

        try {
            $externalIssue = $action->execute($issue, $data['external_issue']);

            return redirect()
                ->route('error-tracker.issues.show', $issue)
                ->with('status', 'GitHub issue #'.$externalIssue->external_number.' linked.');
        } catch (ExternalIssueTrackerException $exception) {
            return $this->failed($issue, $exception->getMessage());
        }
    }

    public function sync(Issue $issue, ExternalIssue $externalIssue, SyncExternalIssueAction $action)
    {
        $this->ensureBelongsToIssue($issue, $externalIssue);

        try {
            $action->execute($externalIssue);

            return redirect()
                ->route('error-tracker.issues.show', $issue)
                ->with('status', 'External issue refreshed from GitHub.');
        } catch (ExternalIssueTrackerException $exception) {
            return $this->failed($issue, $exception->getMessage());
        }
    }

    public function destroy(Issue $issue, ExternalIssue $externalIssue, UnlinkExternalIssueAction $action)
    {
        $this->ensureBelongsToIssue($issue, $externalIssue);

        $action->execute($externalIssue);

        return redirect()
            ->route('error-tracker.issues.show', $issue)
            ->with('status', 'GitHub issue unlinked successfully. The GitHub issue was not modified.');
    }

    protected function ensureBelongsToIssue(Issue $issue, ExternalIssue $externalIssue): void
    {
        abort_unless((int) $externalIssue->issue_id === (int) $issue->id, 404);
    }

    protected function failed(Issue $issue, string $message)
    {
        return redirect()
            ->route('error-tracker.issues.show', $issue)
            ->with('status', $message);
    }
}
