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
            <h1>Intake Hub</h1>
            <p class="sub">Pull any Notion workspace into Atlas — by export, live crawl, or seed — then explore it as an interactive map.</p>
        </div>
        <div class="head-actions">
            <a class="ghost" href="{{ route('console.migrations.index') }}">Migrations →</a>
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

    {{-- Migrating off another tool (no Notion export to upload yet)? --}}
    <section class="card" style="margin-bottom:20px;display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap">
        <div>
            <h2 style="margin-bottom:4px">Coming from Microsoft Loop or another tool?</h2>
            <p class="muted">Use the browser extension to pull a workspace out of a closed-API tool and export it Notion-ready — then upload it here.</p>
        </div>
        <a class="btn primary" href="{{ route('console.migrations.index') }}">Open Migrations →</a>
    </section>

    {{-- Coverage checklist: every intake method and whether it's wired up. --}}
    <section class="card checklist-card">
        <h2>Intake coverage</h2>
        <p class="muted" style="margin-bottom:14px">The ways a workspace can enter Atlas. Pick the one that fits what you have access to.</p>
        <ul class="checklist">
            @foreach ($intakeMethods as $m)
                <li class="check-item status-{{ $m['status'] }}">
                    <span class="dot"></span>
                    <div class="check-body">
                        <div class="check-top">
                            <strong>{{ $m['label'] }}</strong>
                            @switch($m['status'])
                                @case('ready')<span class="pill ok">Ready</span>@break
                                @case('available')<span class="pill">Available</span>@break
                                @case('needs-token')<span class="pill warn">Needs token</span>@break
                                @case('planned')<span class="pill muted-pill">Planned</span>@break
                            @endswitch
                            @if ($m['count'] > 0)<span class="pill count">{{ $m['count'] }} imported</span>@endif
                        </div>
                        <span class="check-blurb">{{ $m['blurb'] }}</span>
                    </div>
                </li>
            @endforeach
        </ul>
    </section>

    <section class="grid">
        <div class="card">
            <h2>Mapped workspaces</h2>
            @if (empty($workspaces))
                <p class="muted">No maps yet. Use an intake method on the right to add one.</p>
            @else
                <ul class="ws-list">
                    @foreach ($workspaces as $ws)
                        @php
                            $sourceLabel = match($ws['source']) {
                                'notion-crawl' => 'API crawl',
                                'export-upload' => 'Export',
                                'notion-mcp' => 'MCP seed',
                                default => 'Built-in',
                            };
                            $sourceTag = in_array($ws['source'], ['notion-crawl', 'export-upload'], true) ? 'live' : 'static';
                            $removable = in_array($ws['source'], ['notion-crawl', 'export-upload'], true);
                        @endphp
                        <li>
                            <div class="ws-meta">
                                <a class="ws-name" href="{{ route('console.map', $ws['slug']) }}">{{ $ws['name'] }}</a>
                                <div class="ws-tags">
                                    <span class="tag tag-{{ $sourceTag }}">{{ $sourceLabel }}</span>
                                    @if ($ws['nodeCount'])<span class="tag">{{ number_format($ws['nodeCount']) }} nodes</span>@endif
                                    @if ($ws['generatedAt'])<span class="tag">{{ \Illuminate\Support\Carbon::parse($ws['generatedAt'])->diffForHumans() }}</span>@endif
                                </div>
                            </div>
                            <div class="ws-actions">
                                <a class="btn sm" href="{{ route('console.map', $ws['slug']) }}">Open</a>
                                @if ($removable)
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

            <div class="tabs" role="tablist">
                <button class="tab-btn is-active" data-tab="upload" type="button">Upload export</button>
                <button class="tab-btn" data-tab="crawl" type="button">API crawl</button>
                <button class="tab-btn" data-tab="seed" type="button">MCP seed</button>
            </div>

            {{-- Method 1: Upload a Notion export (offline, most complete). --}}
            <div class="tab-panel is-active" data-panel="upload">
                <form method="POST" action="{{ route('workspaces.upload') }}" class="form" enctype="multipart/form-data">
                    @csrf
                    <label>
                        <span>Name</span>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Company Notion" required>
                    </label>
                    <label>
                        <span>Notion export file</span>
                        <input type="file" name="export" accept=".zip,.csv,.md,.html,.htm,.txt" required>
                        <em class="hint-inline">In Notion: ••• → Export → <strong>Markdown &amp; CSV</strong>, include subpages &amp; databases, then upload the .zip here.</em>
                    </label>
                    <button class="btn primary" type="submit">Import export →</button>
                </form>
                <p class="muted" style="margin-top:12px">Most complete &amp; private — it reads the page tree straight off disk, so it covers teamspaces the API can't list. No keys leave your machine.</p>
            </div>

            {{-- Method 2: Live API crawl. --}}
            <div class="tab-panel" data-panel="crawl">
                <form method="POST" action="{{ route('workspaces.store') }}" class="form">
                    @csrf
                    <label>
                        <span>Name</span>
                        <input type="text" name="name" value="{{ old('name') }}" placeholder="Company Notion" required>
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
                        <span>Teamspace roots
                            @if ($discoveredSeed)<button type="button" class="link-btn" id="loadSeed">load discovered ↧</button>@endif
                        </span>
                        <textarea name="teamspaces" id="teamspacesBox" rows="5" placeholder="One per line:&#10;Company Home = 7cb6264d-6634-4dff-aa05-e4b7f2852d24&#10;&#10;…or paste the raw get-teams JSON.">{{ old('teamspaces') }}</textarea>
                    </label>

                    <button class="btn primary" type="submit">Crawl &amp; map →</button>
                </form>
                <p class="muted" style="margin-top:12px">Live, but only sees pages shared with the integration. Best paired with the MCP seed below.</p>
            </div>

            {{-- Method 3: MCP seed instructions. --}}
            <div class="tab-panel" data-panel="seed">
                <ol class="steps">
                    <li>In a Notion MCP client, run <code>get-teams</code> to list your teamspaces.</li>
                    <li>Copy the JSON it returns.</li>
                    <li>Switch to the <strong>API crawl</strong> tab, choose <em>Teamspace roots</em>, and paste it in.</li>
                </ol>
                @if ($discoveredSeed)
                    <p class="muted">Already discovered for this workspace:</p>
                    <pre class="seed-preview">{{ $discoveredSeed }}</pre>
                    <p class="muted">Use <strong>load discovered</strong> on the API crawl tab to drop these in.</p>
                @else
                    <p class="muted">Nothing discovered yet. The REST API can't enumerate teamspaces, so this seed bridges the gap.</p>
                @endif
            </div>

            <details class="how">
                <summary>Which method should I use?</summary>
                <p><strong>Upload export</strong> — you want the most complete map and can export from Notion. Covers everything, no keys.</p>
                <p><strong>API crawl</strong> — you want a live map and have an integration token. Pair with an MCP seed for teamspaces.</p>
                <p class="muted">For very large, fully-recursive crawls the CLI is sturdier: <code>php artisan atlas:ingest --name="…" --teamspaces=roots.json -v</code></p>
            </details>
        </div>
    </section>
</div>

<script>
    // Tabs.
    document.querySelectorAll('.tab-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var tab = btn.dataset.tab;
            document.querySelectorAll('.tab-btn').forEach(function (b) { b.classList.toggle('is-active', b === btn); });
            document.querySelectorAll('.tab-panel').forEach(function (p) {
                p.classList.toggle('is-active', p.dataset.panel === tab);
            });
        });
    });

    // Drop the MCP-discovered teamspaces into the crawl seed box.
    var seedBtn = document.getElementById('loadSeed');
    if (seedBtn) {
        var discovered = @json($discoveredSeed);
        seedBtn.addEventListener('click', function () {
            var box = document.getElementById('teamspacesBox');
            box.value = discovered;
            var radio = document.querySelector('input[name=mode][value=teamspaces]');
            if (radio) { radio.checked = true; }
            box.focus();
        });
    }
</script>
</body>
</html>
