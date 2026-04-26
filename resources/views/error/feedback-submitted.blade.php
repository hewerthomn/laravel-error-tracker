<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Feedback submitted' }}</title>
    <style>
        body {
            margin: 0;
            background: #f8fafc;
            color: #0f172a;
            font-family: Inter, Arial, sans-serif;
        }

        .wrapper {
            max-width: 640px;
            margin: 48px auto;
            padding: 24px;
        }

        .card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 24px;
        }

        .muted {
            color: #64748b;
        }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="card">
            <h1 style="margin-top: 0;">{{ $title }}</h1>
            <p class="muted">{{ $message }}</p>
        </div>
    </div>
</body>
</html>