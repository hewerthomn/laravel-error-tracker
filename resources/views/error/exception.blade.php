<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Something went wrong' }}</title>
    <style>
        :root {
            --bg: #f8fafc;
            --card: #ffffff;
            --border: #e2e8f0;
            --text: #0f172a;
            --muted: #64748b;
            --danger: #dc2626;
            --primary: #2563eb;
            --shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 8px 24px rgba(15, 23, 42, 0.06);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--text);
            font-family: Inter, Arial, sans-serif;
        }

        .wrapper {
            max-width: 760px;
            margin: 48px auto;
            padding: 24px;
        }

        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            box-shadow: var(--shadow);
        }

        .muted {
            color: var(--muted);
        }

        .reference {
            margin-top: 16px;
            padding: 12px 14px;
            border-radius: 12px;
            background: #f1f5f9;
            border: 1px solid var(--border);
            font-size: 14px;
        }

        .form-group {
            margin-top: 16px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
        }

        input, textarea, button {
            width: 100%;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
            font: inherit;
            background: #fff;
        }

        textarea {
            min-height: 140px;
            resize: vertical;
        }

        button {
            background: var(--primary);
            color: #fff;
            border-color: var(--primary);
            cursor: pointer;
            font-weight: 600;
        }

        button:hover {
            background: #1d4ed8;
            border-color: #1d4ed8;
        }

        .errors {
            margin-top: 16px;
            padding: 12px 14px;
            border-radius: 12px;
            background: #fef2f2;
            color: var(--danger);
            border: 1px solid #fecaca;
        }

        .spacer {
            margin-top: 24px;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <h1 style="margin-top: 0;">{{ $title }}</h1>
            <p class="muted">{{ $message }}</p>

            @if (!empty($showReference) && !empty($reference))
                <div class="reference">
                    <strong>Reference:</strong> {{ $reference }}
                </div>
            @endif

            @if (!empty($showFeedbackForm))
                <div class="spacer">
                    <h2 style="margin-bottom: 8px;">Help us understand what happened</h2>
                    <p class="muted" style="margin-top: 0;">
                        You can send extra context about what you were doing when this error happened.
                    </p>

                    @if (!empty($feedbackErrors) && $feedbackErrors->any())
                        <div class="errors">
                            <ul style="margin: 0; padding-left: 18px;">
                                @foreach ($feedbackErrors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('error-tracker.feedback.store', $event->feedback_token) }}">
                        @csrf

                        <input type="hidden" name="page_url" value="{{ $pageUrl ?? request()->fullUrl() }}">

                        @if (!empty($collectName))
                            <div class="form-group">
                                <label for="name">Name</label>
                                <input
                                    id="name"
                                    type="text"
                                    name="name"
                                    value="{{ data_get($oldInput ?? [], 'name', '') }}"
                                >
                            </div>
                        @endif

                        @if (!empty($collectEmail))
                            <div class="form-group">
                                <label for="email">Email</label>
                                <input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value="{{ data_get($oldInput ?? [], 'email', '') }}"
                                >
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="message">What were you doing when this happened?</label>
                            <textarea
                                id="message"
                                name="message"
                                required
                            >{{ data_get($oldInput ?? [], 'message', '') }}</textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit">Send feedback</button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </div>
</body>
</html>