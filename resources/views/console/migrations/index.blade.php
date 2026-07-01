<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Migrations · {{ config('atlas.title') }}</title>
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
            <h1>Migrations</h1>
            <p class="sub">Pull a workspace out of another tool — starting with Microsoft Loop — straight into a Notion-ready export, using the Atlas browser extension.</p>
        </div>
        <div class="head-actions">
            <a class="ghost" href="{{ route('console.index') }}">← Intake Hub</a>
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

    {{-- Extension pairing --}}
    <section class="card">
        <h2>Browser extension</h2>
        <p class="muted" style="margin-bottom:14px">
            The migration happens inside your own logged-in browser tab (Loop has no API), so it needs the Atlas extension.
            Pair it once — you stay logged into the console, and the extension inherits that trust.
        </p>

        @if ($extensions->isEmpty())
            <div class="ext-status not-connected"><span class="dot"></span> No extension paired yet.</div>
        @else
            <ul class="ws-list" style="margin-bottom:14px">
                @foreach ($extensions as $ext)
                    <li>
                        <div class="ws-meta">
                            <span class="ws-name">{{ $ext->name ?: 'Paired extension' }}</span>
                            <div class="ws-tags">
                                <span class="tag tag-live">Connected</span>
                                @if ($ext->last_used_at)<span class="tag">last used {{ $ext->last_used_at->diffForHumans() }}</span>@endif
                            </div>
                        </div>
                        <div class="ws-actions">
                            <form method="POST" action="{{ route('console.migrations.extensions.revoke', $ext) }}" onsubmit="return confirm('Disconnect this extension?')">
                                @csrf
                                <button class="btn sm danger" type="submit">Disconnect</button>
                            </form>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif

        <div class="pair-row">
            <button class="btn primary" type="button" id="genCode">Generate pairing code</button>
            <span class="muted" id="pairHint">Click, then paste the code into the extension popup.</span>
        </div>

        <div class="pair-out" id="pairOut" hidden>
            <div class="code-box"><span class="code-label">Pairing code</span><code id="pairCode">--------</code></div>
            <div class="code-box"><span class="code-label">API URL</span><code id="pairApi">{{ url('/api') }}</code></div>
            <p class="muted" id="pairExpiry"></p>
        </div>

        <details class="how" style="margin-top:16px">
            <summary>First time? Install the extension</summary>
            <p>1. In Chrome/Edge, open <code>chrome://extensions</code> and turn on <strong>Developer mode</strong>.</p>
            <p>2. Click <strong>Load unpacked</strong> and select the <code>extension/</code> folder from the Atlas repo.</p>
            <p>3. Open the extension popup, paste the <strong>API URL</strong> and <strong>pairing code</strong> above, and hit Connect.</p>
            <p class="muted">The code expires quickly — generate a fresh one if it lapses.</p>
        </details>
    </section>

    <section class="grid">
        {{-- Start a migration --}}
        <div class="card">
            <h2>Start a migration</h2>
            <form method="POST" action="{{ route('console.migrations.start') }}" class="form">
                @csrf
                <label>
                    <span>Source tool</span>
                    <select name="source" required>
                        @foreach ($sources as $s)
                            <option value="{{ $s['key'] }}" @disabled($s['status'] !== 'ready')>
                                {{ $s['label'] }}@if($s['status'] !== 'ready') — soon @endif
                            </option>
                        @endforeach
                    </select>
                </label>
                <label>
                    <span>Name this migration</span>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Strategic Alliances (Loop)" required>
                </label>
                <button class="btn primary" type="submit" @disabled(! $hasReadySource)>Queue migration →</button>
            </form>
            <p class="muted" style="margin-top:12px">
                Queues a run. Then: open the source tool in your browser (logged in), open the paired extension, and it picks up the queued run and streams pages here live.
            </p>
        </div>

        {{-- Source coverage --}}
        <div class="card">
            <h2>What we can migrate</h2>
            <ul class="checklist" style="grid-template-columns:1fr">
                @foreach ($sources as $s)
                    <li class="check-item status-{{ $s['status'] === 'ready' ? 'ready' : 'planned' }}">
                        <span class="dot"></span>
                        <div class="check-body">
                            <div class="check-top">
                                <strong>{{ $s['label'] }}</strong>
                                @if ($s['status'] === 'ready')<span class="pill ok">Ready</span>
                                @else<span class="pill muted-pill">Planned</span>@endif
                            </div>
                            <span class="check-blurb">{{ $s['blurb'] }}</span>
                        </div>
                    </li>
                @endforeach
            </ul>
            <p class="muted" style="margin-top:12px">Each source is a small adapter in the extension. New tools plug in here without changing the pipeline.</p>
        </div>
    </section>

    {{-- Runs --}}
    <section class="card" style="margin-top:20px">
        <h2>Migrations</h2>
        @if ($runs->isEmpty())
            <p class="muted">No migrations yet. Pair the extension and queue one above.</p>
        @else
            <ul class="ws-list">
                @foreach ($runs as $run)
                    <li>
                        <div class="ws-meta">
                            <a class="ws-name" href="{{ route('console.migrations.show', $run) }}">{{ $run->name }}</a>
                            <div class="ws-tags">
                                <span class="tag tag-static">{{ $run->sourceLabel() }}</span>
                                <span class="tag run-{{ $run->status }}">{{ ucfirst($run->status) }}</span>
                                <span class="tag">{{ $run->succeeded }} ok</span>
                                @if ($run->failed)<span class="tag run-failed">{{ $run->failed }} failed</span>@endif
                                <span class="tag">{{ $run->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        <div class="ws-actions">
                            <a class="btn sm" href="{{ route('console.migrations.show', $run) }}">Open</a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</div>

<script>
    const csrf = document.querySelector('meta[name=csrf-token]').content;
    const genBtn = document.getElementById('genCode');
    genBtn.addEventListener('click', async function () {
        genBtn.disabled = true;
        genBtn.textContent = 'Generating…';
        try {
            const res = await fetch(@json(route('console.migrations.pair-code')), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
            });
            const data = await res.json();
            document.getElementById('pairCode').textContent = data.code;
            document.getElementById('pairApi').textContent = data.apiBase;
            const mins = Math.round((data.expiresIn || 600) / 60);
            document.getElementById('pairExpiry').textContent = 'Expires in about ' + mins + ' minute' + (mins === 1 ? '' : 's') + '.';
            document.getElementById('pairOut').hidden = false;
            document.getElementById('pairHint').textContent = 'Paste these into the extension popup.';
        } catch (e) {
            document.getElementById('pairHint').textContent = 'Could not generate a code — try again.';
        } finally {
            genBtn.disabled = false;
            genBtn.textContent = 'Generate pairing code';
        }
    });
</script>
</body>
</html>
