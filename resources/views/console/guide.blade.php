<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Guide · {{ config('atlas.title') }}</title>
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/console.css') }}?v={{ @filemtime(public_path('css/console.css')) ?: 1 }}">
</head>
<body>
<div class="wrap wrap-narrow">
    <header class="head">
        <div>
            <a class="crumb" href="{{ route('console.index') }}">‹ Workspaces</a>
            <h1>How intake works</h1>
            <p class="sub">Atlas maps a Notion workspace from many sources, then reconciles them into one picture.</p>
        </div>
    </header>

    <div class="card prose">
        <h2>The two halves of Atlas</h2>
        <p><strong>Public atlas (<code>/</code>)</strong> is the conceptual map anyone can see. <strong>This console (<code>/console</code>)</strong> is the private backend where you import a real workspace and explore it. Real workspace data never appears on the public side.</p>

        <h2>Why many sources?</h2>
        <p>No single export is complete. A live API crawl can't see admin-deleted pages or teamspaces it isn't invited to; Content Analytics undercounts; the export ZIP lacks behavior. The trick is to <strong>triangulate</strong>: take the canonical page list from <strong>AdminContentSearch</strong>, then corroborate recency, access, retention, and behavior from the other sources. When two sources disagree, that disagreement is itself a finding.</p>

        <h2>The order that matters</h2>
        <ol>
            <li><strong>Seed first.</strong> Import <em>AdminContentSearch (Active)</em> — it's the canonical list of every live page. Everything else hangs off it.</li>
            <li><strong>Add the retention seed.</strong> <em>AdminContentSearch (Retained)</em> surfaces admin-deleted pages held under legal hold — nothing else can.</li>
            <li><strong>Layer in behavior &amp; access.</strong> The <em>Audit Log</em>, <em>Content Analytics</em>, and <em>Members</em> exports add who/what/when and permissions.</li>
            <li><strong>Fill structure.</strong> The <em>workspace export ZIP</em> and a <em>live API scan</em> add the page tree and current shape.</li>
        </ol>

        <h2>How to use it</h2>
        <ol>
            <li>Create a workspace on the dashboard (one per Notion org).</li>
            <li>On its intake page, work down the checklist — upload each export, or run the live API scan with a token.</li>
            <li>The progress bar tracks how many file sources you've imported. Uploads are stored privately on the server and never web-served.</li>
            <li>Once a scan or reconciliation produces a map, open it from <strong>View map</strong>.</li>
        </ol>

        <h2>On credentials</h2>
        <p>The <strong>live API scan</strong> takes a full-access token that's used for that one request and <strong>never written to disk</strong>. <strong>Connect Notion (OAuth)</strong> is the cleaner long-term option — it scans everything you can access with no token handling — but needs a registered Notion OAuth app first (see its checklist entry). Admin CSV exports require no credentials in Atlas at all; you generate them in Notion and upload the files.</p>
    </div>

    <div class="card">
        <h2>Source reference</h2>
        <table class="ref-table">
            <thead><tr><th>Source</th><th>Role</th><th>What it gives you</th></tr></thead>
            <tbody>
            @foreach ($catalog as $s)
                <tr>
                    <td>{{ $s['label'] }}@if (!empty($s['seed'])) <span class="badge-seed">SEED</span>@endif</td>
                    <td>{{ $s['role'] }}</td>
                    <td>{{ $s['summary'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
