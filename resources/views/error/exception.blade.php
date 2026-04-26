<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Something went wrong' }}</title>
    <style>
        :root {
            --bg: #f6f7f9;
            --panel: #ffffff;
            --border: #e2e8f0;
            --text: #111827;
            --muted: #6b7280;
            --danger: #b91c1c;
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --surface: #f9fafb;
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
            max-width: 680px;
            margin: 40px auto;
            padding: 20px;
        }

        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 22px;
        }

        h1 {
            margin: 0;
            font-size: 24px;
            line-height: 1.2;
        }

        h2 {
            margin: 0;
            font-size: 17px;
            line-height: 1.3;
        }

        .muted {
            color: var(--muted);
            line-height: 1.55;
        }

        .reference {
            margin-top: 16px;
            padding: 12px 14px;
            border-radius: 8px;
            background: var(--surface);
            border: 1px solid var(--border);
            font-size: 14px;
        }

        .form-group {
            margin-top: 14px;
        }

        .identity-grid {
            display: grid;
            gap: 12px;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            margin-top: 16px;
        }

        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
        }

        input, textarea, button {
            width: 100%;
            padding: 11px 12px;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
            font: inherit;
            background: #fff;
        }

        input[readonly] {
            background: var(--surface);
            color: var(--muted);
            cursor: not-allowed;
        }

        .form-note {
            margin-top: 8px;
            font-size: 13px;
            color: var(--muted);
        }

        textarea {
            min-height: 132px;
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
            background: var(--primary-dark);
            border-color: var(--primary-dark);
        }

        .errors {
            margin-top: 16px;
            padding: 12px 14px;
            border-radius: 8px;
            background: #fef2f2;
            color: var(--danger);
            border: 1px solid #fecaca;
        }

        .spacer {
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }

        @media (max-width: 640px) {
            .wrapper {
                margin: 20px auto;
                padding: 14px;
            }

            .panel {
                padding: 18px;
            }

            .identity-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <main class="panel">
            <h1>{{ $title }}</h1>
            <p class="muted">{{ $message }}</p>

            @if (!empty($showReference) && !empty($reference))
                <div class="reference">
                    <strong>Reference:</strong> {{ $reference }}
                </div>
            @endif

            @if (!empty($showFeedbackForm))
                <div class="spacer">
                    <h2>Help us understand what happened</h2>
                    <p class="muted" style="margin-top: 0;">
                        Share what you were doing before the error. This is attached to the error reference above.
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

                        @php
                            $nameValue = $feedbackName ?? data_get($oldInput ?? [], 'name', '');
                            $emailValue = $feedbackEmail ?? data_get($oldInput ?? [], 'email', '');
                        @endphp

                        <div class="identity-grid">
                            @if (!empty($collectName))
                                <div>
                                    <label for="name">Name</label>
                                    <input
                                        id="name"
                                        type="text"
                                        name="name"
                                        value="{{ $nameValue }}"
                                        autocomplete="name"
                                        @readonly(!empty($isFeedbackUserAuthenticated))
                                    >
                                </div>
                            @endif

                            @if (!empty($collectEmail))
                                <div>
                                    <label for="email">Email</label>
                                    <input
                                        id="email"
                                        type="email"
                                        name="email"
                                        value="{{ $emailValue }}"
                                        autocomplete="email"
                                        @readonly(!empty($isFeedbackUserAuthenticated))
                                    >
                                </div>
                            @endif
                        </div>

                        @if (!empty($isFeedbackUserAuthenticated))
                            <div class="form-note">
                                Signed-in user information is attached automatically and cannot be changed here.
                            </div>
                        @endif

                        <div class="form-group">
                            <label for="message">What were you doing when this happened?</label>
                            <textarea
                                id="message"
                                name="message"
                                required
                                maxlength="{{ (int) config('error-tracker.feedback.max_length', 5000) }}"
                            >{{ data_get($oldInput ?? [], 'message', '') }}</textarea>
                        </div>

                        <div class="form-group">
                            <button type="submit">Send feedback</button>
                        </div>
                    </form>
                </div>
            @endif
        </main>
    </div>
</body>
</html>
