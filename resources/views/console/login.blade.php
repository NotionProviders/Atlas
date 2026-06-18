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
<div class="login-wrap">
    <form method="POST" action="{{ route('console.login.attempt') }}" class="card login-card">
        @csrf
        <span class="kicker">Atlas Console</span>
        <h1>Sign in</h1>
        <p class="sub">Private backend for mapping Notion workspaces.</p>

        @if ($locked)
            <div class="flash err">The console is locked. Set <code>CONSOLE_PASSWORD</code> in <code>.env</code> to enable login.</div>
        @endif
        @error('password')<div class="flash err">{{ $message }}</div>@enderror

        <label>
            <span>Password</span>
            <input type="password" name="password" autocomplete="current-password" autofocus required>
        </label>

        <button class="btn primary" type="submit">Enter console →</button>
        <a class="back-link" href="{{ route('atlas.index') }}">← Public atlas</a>
    </form>
</div>
</body>
</html>
