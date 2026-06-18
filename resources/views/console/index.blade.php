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
            <p class="sub">Map any Notion workspace, then explore it as an interactive atlas.</p>
        </div>
        <div class="head-actions">
            <a class="ghost" href="{{ route('atlas.index') }}">Public atlas ↗</a>
            <form method="POST" action="{{ route('console.logout') }}">@csrf<button class="ghost" type="submit">Log out</button></form>
        </div>
    </header>

    @if (session('status'))
        <div class="flash ok">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="flash err">
            @foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <section class="grid">
        <div class="card">
            <h2>Mapped workspaces</h2>
            @if (empty($workspaces))
                <p class="muted">No maps yet. Create one on the right.</p>
            @else
                <ul class="ws-list">
                    @foreach ($workspaces as $ws)
                        <li>
                            <div class="ws-meta">
                                <a class="ws-name" href="{{ route('console.map', $ws['slug']) }}">{{ $ws['name'] }}</a>
                                <div class="ws-tags">
                                    <span class="tag tag-{{ $ws['source'] === 'notion-crawl' ? 'live' : 'static' }}">{{ $ws['source'] }}</span>
                                    @if ($ws['nodeCount'])<span class="tag">{{ number_format($ws['nodeCount']) }} nodes</span>@endif
                                    @if ($ws['generatedAt'])<span class="tag">{{ \Illuminate\Support\Carbon::parse($ws['generatedAt'])->diffForHumans() }}</span>@endif
                                </div>
                            </div>
                            <div class="ws-actions">
                                <a class="btn sm" href="{{ route('console.map', $ws['slug']) }}">Open</a>
                                @if ($ws['source'] === 'notion-crawl')
                                <form method="POST" action="{{ route('workspaces.destroy', $ws['slug']) }}" onsubmit="return confirm('Remove this map?')">
                                    @csrf @method('DELETE')
                                    <button class="btn sm danger" type="submit">Remove</button>
                                </form>
                                @endif
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div class="card">
            <h2>Add a workspace</h2>
            <form method="POST" action="{{ route('workspaces.store') }}" class="form">
                @csrf
                <label>
                    <span>Name</span>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Formosa EV HQ" required>
                </label>

                <label>
                    <span>Notion API token @if ($hasToken)<em class="hint-inline">— using NOTION_API_KEY from .env if blank</em>@endif</span>
                    <input type="password" name="token" placeholder="ntn_… (full-access integration token)" @if(!$hasToken) required @endif autocomplete="off">
                </label>

                <fieldset class="modes">
                    <legend>Where to start</legend>
                    <label class="radio">
                        <input type="radio" name="mode" value="teamspaces" {{ old('mode', 'teamspaces') === 'teamspaces' ? 'checked' : '' }}>
                        <span><strong>Teamspace roots</strong> — paste them (the API can't list teamspaces)</span>
                    </label>
                    <label class="radio">
                        <input type="radio" name="mode" value="discover" {{ old('mode') === 'discover' ? 'checked' : '' }}>
                        <span><strong>Auto-discover</strong> — crawl every top-level page/database the token can see</span>
                    </label>
                </fieldset>

                <label>
                    <span>Teamspace roots</span>
                    <textarea name="teamspaces" rows="5" placeholder="One per line:&#10;Formosa EV HQ = fbbe1391-befa-44f2-aa7e-cc72c2d2d3c8&#10;&#10;…or paste the raw get-teams JSON.">{{ old('teamspaces') }}</textarea>
                </label>

                <button class="btn primary" type="submit">Crawl &amp; map →</button>
            </form>

            <details class="how">
                <summary>How to get teamspace roots</summary>
                <p>The Notion REST API can't enumerate teamspaces, so seed them once. In a Notion MCP client run <code>get-teams</code> and paste the JSON above, or list <code>Name = page-id</code> lines. The crawler then recurses from each root through every page and nested database.</p>
                <p class="muted">Large workspaces can take a while. For "everything, fully recursive" runs, the CLI is sturdier: <code>php artisan atlas:ingest --name="…" --teamspaces=roots.json -v</code></p>
            </details>
        </div>
    </section>
</div>
</body>
</html>
