<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Intake · {{ $manifest['name'] }} · {{ config('atlas.title') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/console.css') }}?v={{ @filemtime(public_path('css/console.css')) ?: 1 }}">
</head>
<body>
@php
    $imported = collect($manifest['sources'] ?? []);
    $doneUploads = collect($uploadKeys)->filter(fn ($k) => $imported->has($k))->count();
    $pct = count($uploadKeys) ? round(100 * $doneUploads / count($uploadKeys)) : 0;
@endphp
<div class="wrap">
    <header class="head">
        <div>
            <a class="crumb" href="{{ route('console.index') }}">‹ Workspaces</a>
            <h1>{{ $manifest['name'] }}</h1>
            <p class="sub">Import each source below. AdminContentSearch is the canonical seed; the rest corroborate it.</p>
        </div>
        <div class="head-actions">
            <a class="ghost" href="{{ route('console.guide') }}">Guide</a>
            @if ($hasMap)<a class="ghost" href="{{ route('console.map', $slug) }}">View map ↗</a>@endif
        </div>
    </header>

    @if (session('status'))<div class="flash ok">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash err">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

    <div class="intake-progress">
        <div class="intake-progress-bar"><span style="width: {{ $pct }}%"></span></div>
        <span class="intake-progress-label">{{ $doneUploads }} / {{ count($uploadKeys) }} file sources imported</span>
    </div>

    <div class="sources">
        @foreach ($catalog as $s)
            @php($entry = $imported->get($s['key']))
            <div class="source {{ $entry ? 'is-done' : '' }}">
                <div class="source-head">
                    <div class="source-title">
                        <span class="status-dot"></span>
                        <strong>{{ $s['label'] }}</strong>
                        @if (!empty($s['seed']))<span class="badge-seed" title="Primary page-enumeration seed">SEED</span>@endif
                        <span class="role">{{ $s['role'] }}</span>
                    </div>
                    @if ($entry)
                        <span class="metric">{{ $entry['metric'] ?? 'imported' }}</span>
                    @endif
                </div>

                <p class="source-summary">{{ $s['summary'] }}</p>

                @if ($entry)
                    <div class="source-done">
                        <span class="done-file">
                            ✓ {{ $entry['filename'] ?? ucfirst($entry['kind'] ?? 'imported') }}
                            @if (!empty($entry['importedAt'])) · {{ \Illuminate\Support\Carbon::parse($entry['importedAt'])->diffForHumans() }}@endif
                        </span>
                        <form method="POST" action="{{ route('intake.remove', [$slug, $s['key']]) }}">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn sm danger">Remove</button>
                        </form>
                    </div>
                @endif

                <div class="source-action">
                    @if ($s['kind'] === 'upload')
                        <form method="POST" action="{{ route('intake.upload', $slug) }}" enctype="multipart/form-data" class="upload-form">
                            @csrf
                            <input type="hidden" name="source" value="{{ $s['key'] }}">
                            <input type="file" name="file" accept="{{ $s['accept'] }}" required>
                            <button type="submit" class="btn sm">{{ $entry ? 'Replace' : 'Upload' }} {{ $s['accept'] }}</button>
                        </form>
                    @elseif ($s['kind'] === 'api')
                        <details class="api-scan">
                            <summary class="btn sm">{{ $entry ? 'Re-run' : 'Run' }} live API scan</summary>
                            <form method="POST" action="{{ route('intake.scan', $slug) }}" class="form">
                                @csrf
                                <label><span>Full-access token (used once, never stored)</span>
                                    <input type="password" name="token" placeholder="ntn_…" autocomplete="off" required></label>
                                <fieldset class="modes">
                                    <legend>Where to start</legend>
                                    <label class="radio"><input type="radio" name="mode" value="teamspaces" checked><span>Teamspace roots (paste below)</span></label>
                                    <label class="radio"><input type="radio" name="mode" value="discover"><span>Auto-discover everything the token can see</span></label>
                                </fieldset>
                                <label><span>Teamspace roots</span>
                                    <textarea name="teamspaces" rows="3" placeholder="Name = page-id per line, or paste get-teams JSON"></textarea></label>
                                <button type="submit" class="btn primary">Scan now</button>
                            </form>
                        </details>
                    @else {{-- oauth --}}
                        @if ($oauthConfigured)
                            <a class="btn sm" href="#">Connect Notion</a>
                        @else
                            <span class="btn sm disabled" title="Set NOTION_OAUTH_CLIENT_ID / SECRET in .env">Connect Notion — setup required</span>
                        @endif
                    @endif
                </div>

                <details class="how">
                    <summary>How to get this @if (!empty($s['expected'])) · <em class="muted">expect: {{ $s['expected'] }}</em>@endif</summary>
                    <ol>@foreach ($s['how_to'] as $step)<li>{{ $step }}</li>@endforeach</ol>
                </details>
            </div>
        @endforeach
    </div>
</div>
</body>
</html>
