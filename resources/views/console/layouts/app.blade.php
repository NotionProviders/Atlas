<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Console') — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/console.css') }}?v={{ @filemtime(public_path('css/console.css')) ?: 1 }}">
    @stack('head')
</head>
<body class="console-body">
    <header class="console-header">
        <div class="console-header-inner">
            <a href="{{ route('console.projects.index') }}" class="console-brand">Atlas Console</a>
            <a href="{{ route('console.canonical.index') }}" class="console-nav-link">Canonical DBs</a>
            @isset($project)
                <span class="console-breadcrumb-sep">/</span>
                <a href="{{ route('console.projects.show', $project) }}" class="console-breadcrumb">{{ $project->name }}</a>
            @endisset
            @yield('breadcrumb')
            <div class="console-header-actions">
                @auth
                    <span class="console-user">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('console.logout') }}" class="console-logout-form">
                        @csrf
                        <button type="submit" class="console-link-btn">Log out</button>
                    </form>
                @endauth
            </div>
        </div>
    </header>

    @if (session('status'))
        <div class="console-flash">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="console-flash console-flash-error">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <main class="console-main">
        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
