<?php

namespace Hewerthomn\ErrorTracker\Models\Concerns;

trait UsesErrorTrackerConnection
{
    public function getConnectionName()
    {
        return config('error-tracker.database.connection') ?: parent::getConnectionName();
    }
}
