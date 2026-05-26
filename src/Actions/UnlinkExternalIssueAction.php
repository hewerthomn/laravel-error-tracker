<?php

namespace Hewerthomn\ErrorTracker\Actions;

use Hewerthomn\ErrorTracker\Models\ExternalIssue;

class UnlinkExternalIssueAction
{
    public function execute(ExternalIssue $externalIssue): void
    {
        $externalIssue->delete();
    }
}
