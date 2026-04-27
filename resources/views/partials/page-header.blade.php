@php
    $breadcrumbs = $breadcrumbs ?? [];
    $title = $title ?? '';
    $titleUrl = $titleUrl ?? null;
    $subtitle = $subtitle ?? null;
    $badges = $badges ?? [];
    $actions = $actions ?? null;
    $showHomeLink = config('error-tracker.dashboard.show_home_link', true);
    $homeUrl = config('error-tracker.dashboard.app_home_url', '/');
    $homeLabel = config('error-tracker.dashboard.app_home_label', 'Back to app');
@endphp

<div class="title-row">
    <div class="title-main">
        <div class="page-breadcrumbs">
            @if ($showHomeLink)
                <a
                    href="{{ $homeUrl }}"
                    class="home-link-chip"
                    title="{{ $homeLabel }}"
                    aria-label="{{ $homeLabel }}"
                >
                    ⌂
                </a>
            @endif

            @foreach ($breadcrumbs as $index => $breadcrumb)
                @if ($index > 0 || $showHomeLink)
                    <span class="muted">/</span>
                @endif

                @if (!empty($breadcrumb['url']))
                    <a href="{{ $breadcrumb['url'] }}">{{ $breadcrumb['label'] }}</a>
                @else
                    <span class="muted">{{ $breadcrumb['label'] }}</span>
                @endif
            @endforeach
        </div>

        <div class="page-title-line">
            <h1>
                @if ($titleUrl)
                    <a href="{{ $titleUrl }}" class="page-title-link">{{ $title }}</a>
                @else
                    {{ $title }}
                @endif
            </h1>

            @if ($subtitle)
                <span class="page-title-app-name">{{ $subtitle }}</span>
            @endif
        </div>
    </div>

    @if ($actions)
        <div class="title-meta">
            @include($actions)
        </div>
    @elseif (!empty($badges))
        <div class="title-meta">
            @foreach ($badges as $badge)
                <span class="badge {{ $badge['class'] ?? 'badge-neutral' }}">
                    {{ $badge['label'] }}
                </span>
            @endforeach
        </div>
    @endif
</div>
