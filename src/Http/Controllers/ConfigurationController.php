<?php

namespace Hewerthomn\ErrorTracker\Http\Controllers;

use Hewerthomn\ErrorTracker\Support\Diagnostics\ConfigurationPresenter;
use Illuminate\Routing\Controller;

class ConfigurationController extends Controller
{
    public function show(ConfigurationPresenter $presenter)
    {
        /** @var view-string $view */
        $view = 'error-tracker::dashboard.configuration';

        return view($view, [
            'diagnostics' => $presenter->present(),
        ]);
    }
}
