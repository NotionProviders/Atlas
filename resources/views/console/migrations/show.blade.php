<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $run->name }} · Migrations</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/console.css') }}?v={{ @filemtime(public_path('css/console.css')) ?: 1 }}">
</head>
<body data-status-url="{{ route('console.migrations.status', $run) }}" data-terminal="{{ $run->isTerminal() ? '1' : '0' }}">
<div class="wrap">
    <header class="head">
        <div>
            <span class="kicker">Atlas Console · Migration</span>
            <h1>{{ $run->name }}</h1>
            <p class="sub">{{ $run->sourceLabel() }} → Notion-ready markdown export.</p>
        </div>
        <div class="head-actions">
            <a class="ghost" href="{{ route('console.migrations.index') }}">← All migrations</a>
            <form method="POST" action="{{ route('console.migrations.destroy', $run) }}" onsubmit="return confirm('Remove this migration and its files?')">
                @csrf @method('DELETE')
                <button class="ghost" type="submit">Remove</button>
            </form>
        </div>
    </header>

    @if (session('status'))
        <div class="flash ok">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="flash err">@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
    @endif

    {{-- Live status --}}
    <section class="card">
        <div class="run-head">
            <span class="pill run-pill" id="statusPill" data-status="{{ $run->status }}">{{ ucfirst($run->status) }}</span>
            <div class="run-stats">
                <span><strong id="stat-succeeded">{{ $run->succeeded }}</strong> ok</span>
                <span><strong id="stat-failed">{{ $run->failed }}</strong> failed</span>
                <span><strong id="stat-attachments">{{ $run->attachments }}</strong> attachments</span>
                <span><strong id="stat-total">{{ $run->total }}</strong> total</span>
            </div>
        </div>
        <div class="progress"><div class="progress-bar" id="progressBar" style="width: {{ $run->progressPercent() }}%"></div></div>

        @if ($run->status === 'pending')
            <p class="muted" id="waitingNote" style="margin-top:12px">Waiting for the extension to pick this up. Open the source tool in your browser (logged in) and run it from the paired extension.</p>
        @endif

        <div class="run-actions" id="downloadRow" @if(! ($hasZip || $run->status === 'completed')) hidden @endif style="margin-top:16px">
            <a class="btn primary" href="{{ route('console.migrations.download', $run) }}">Download Notion export (.zip)</a>
            <span class="muted">Import into Notion: ••• → Import → Markdown &amp; CSV → pick this zip.</span>
        </div>
    </section>

    {{-- Punch list --}}
    @if ($run->report)
        @php $report = $run->report; @endphp
        <section class="card" id="reportCard" style="margin-top:20px">
            <h2>Notion import punch list</h2>
            <p class="muted" style="margin-bottom:14px">Scanned <strong>{{ $report['scanned'] ?? 0 }}</strong> pages · <strong>{{ $report['total_issues'] ?? 0 }}</strong> item(s) flagged. Review before importing.</p>
            @php $anyIssue = false; @endphp
            @foreach (($report['categories'] ?? []) as $key => $cat)
                @if (! empty($cat['items']))
                    @php $anyIssue = true; @endphp
                    <div class="punch punch-{{ $cat['severity'] }}">
                        <div class="punch-head"><strong>{{ str_replace('_', ' ', ucfirst($key)) }}</strong> <span class="muted">{{ $cat['description'] }} ({{ count($cat['items']) }})</span></div>
                        <ul class="punch-list">
                            @foreach (array_slice($cat['items'], 0, 10) as $item)<li>{{ $item }}</li>@endforeach
                            @if (count($cat['items']) > 10)<li class="muted">… and {{ count($cat['items']) - 10 }} more</li>@endif
                        </ul>
                    </div>
                @endif
            @endforeach
            @if (! $anyIssue)
                <p class="ext-status" style="color:var(--green)"><span class="dot" style="background:var(--green)"></span> No issues found — export looks clean.</p>
            @endif
            @if (($report['reattach_count'] ?? 0) > 0)
                <p class="muted" style="margin-top:12px"><strong>{{ $report['reattach_count'] }}</strong> link(s)/attachment(s) need manual re-attachment — see <code>REATTACH.md</code> inside the export.</p>
            @endif
        </section>
    @endif

    {{-- Per-page log --}}
    <section class="card" style="margin-top:20px">
        <h2>Pages <span class="muted" style="font-weight:400">(<span id="itemCount">{{ $items->count() }}</span>)</span></h2>
        @if ($items->isEmpty())
            <p class="muted" id="emptyLog">No pages ingested yet.</p>
        @endif
        <ul class="log" id="itemLog">
            @foreach ($items as $item)
                <li class="log-{{ $item->status }}">
                    <span class="log-icon"></span>
                    <span class="log-title">{{ $item->title }}</span>
                    @if ($item->path)<span class="log-path">{{ $item->path }}</span>@endif
                    @if ($item->error)<span class="log-err">{{ $item->error }}</span>@endif
                </li>
            @endforeach
        </ul>
    </section>
</div>

<script>
    (function () {
        const body = document.body;
        if (body.dataset.terminal === '1') return; // nothing to poll

        const url = body.dataset.statusUrl;
        const ICONS = { ok: '✓', empty: '○', failed: '✗', skipped: '⋯', pending: '…' };

        async function poll() {
            let data;
            try {
                const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
                data = await res.json();
            } catch (e) { return schedule(); }

            const run = data.run;
            document.getElementById('stat-succeeded').textContent = run.succeeded;
            document.getElementById('stat-failed').textContent = run.failed;
            document.getElementById('stat-attachments').textContent = run.attachments;
            document.getElementById('stat-total').textContent = run.total;
            document.getElementById('progressBar').style.width = run.progress + '%';

            const pill = document.getElementById('statusPill');
            pill.textContent = run.status.charAt(0).toUpperCase() + run.status.slice(1);
            pill.dataset.status = run.status;

            // Rebuild the page log.
            const log = document.getElementById('itemLog');
            if (data.items && data.items.length) {
                const empty = document.getElementById('emptyLog');
                if (empty) empty.remove();
                log.innerHTML = data.items.map(function (i) {
                    return '<li class="log-' + i.status + '"><span class="log-icon">' + (ICONS[i.status] || '') + '</span>' +
                        '<span class="log-title">' + escapeHtml(i.title) + '</span>' +
                        (i.path ? '<span class="log-path">' + escapeHtml(i.path) + '</span>' : '') +
                        (i.error ? '<span class="log-err">' + escapeHtml(i.error) + '</span>' : '') + '</li>';
                }).join('');
                document.getElementById('itemCount').textContent = data.items.length;
            }

            if (data.hasZip || run.status === 'completed') {
                document.getElementById('downloadRow').hidden = false;
            }

            const terminal = ['completed', 'failed', 'canceled'].includes(run.status);
            if (terminal) {
                // Reload once to render the punch-list report server-side.
                window.location.reload();
                return;
            }
            schedule();
        }

        function schedule() { setTimeout(poll, 2500); }
        function escapeHtml(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
        schedule();
    })();
</script>
</body>
</html>
