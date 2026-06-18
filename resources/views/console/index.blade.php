<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Console · {{ config('atlas.title') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/console.css') }}?v={{ @filemtime(public_path('css/console.css')) ?: 1 }}">
</head>
<body>
<div class="wrap">
    <header class="head">
        <div>
            <span class="kicker">Atlas Console</span>
            <h1>Workspaces</h1>
            <p class="sub">Import a Notion workspace's footprint from every source, then explore it as an interactive atlas.</p>
        </div>
        <div class="head-actions">
            <a class="ghost" href="{{ route('console.guide') }}">Guide</a>
            <a class="ghost" href="{{ route('atlas.index') }}">Public atlas ↗</a>
            <form method="POST" action="{{ route('console.logout') }}">@csrf<button class="ghost" type="submit">Log out</button></form>
        </div>
    </header>

    @if (session('status'))<div class="flash ok">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="flash err">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>@endif

    <section class="grid">
        <div class="card">
            <h2>Your workspaces</h2>
            @if (empty($workspaces))
                <p class="muted">No workspaces yet. Create one on the right to start importing.</p>
            @else
                <ul class="ws-list">
                    @foreach ($workspaces as $ws)
                        <li>
                            <div class="ws-meta">
                                <a class="ws-name" href="{{ route('intake.show', $ws['slug']) }}">{{ $ws['name'] }}</a>
                                <div class="ws-tags">
                                    <span class="tag">{{ $ws['importedUploads'] }}/{{ $ws['totalUploads'] }} sources</span>
                                    @if ($ws['hasApiScan'])<span class="tag tag-live">api scan</span>@endif
                                    @if ($ws['hasMap'])<span class="tag tag-static">map ready</span>@endif
                                </div>
                                <div class="progress"><span style="width: {{ $ws['totalUploads'] ? round(100 * $ws['importedUploads'] / $ws['totalUploads']) : 0 }}%"></span></div>
                            </div>
                            <div class="ws-actions">
                                <a class="btn sm" href="{{ route('intake.show', $ws['slug']) }}">Intake</a>
                                @if ($ws['hasMap'])<a class="btn sm" href="{{ route('console.map', $ws['slug']) }}">Map</a>@endif
                                <form method="POST" action="{{ route('workspaces.destroy', $ws['slug']) }}" onsubmit="return confirm('Remove this workspace and all imported files?')">
                                    @csrf @method('DELETE')
                                    <button class="btn sm danger" type="submit">Remove</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="card">
            <h2>New workspace</h2>
            <form method="POST" action="{{ route('workspaces.store') }}" class="form">
                @csrf
                <label>
                    <span>Name</span>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Formosa EV HQ" required>
                </label>
                <button class="btn primary" type="submit">Create &amp; open intake →</button>
            </form>
            <p class="muted" style="margin-top:14px">A workspace is a container for one Notion org's footprint. After creating it you'll get an intake checklist covering admin exports, the audit log, members, the workspace ZIP, and a live API scan. New to this? <a href="{{ route('console.guide') }}">Read the guide</a>.</p>
        </div>
    </section>
</div>
</body>
</html>
