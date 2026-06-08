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
            @isset($project)
                <span class="console-breadcrumb-sep">/</span>
                <a href="{{ route('console.projects.show', $project) }}" class="console-breadcrumb">{{ $project->name }}</a>
            @endisset
            @yield('breadcrumb')

            <div class="console-header-actions">
                @auth
                    <div class="console-gear-menu" id="console-gear-menu">
                        <button type="button"
                                class="console-gear-btn"
                                id="console-gear-toggle"
                                aria-label="Settings menu"
                                aria-expanded="false"
                                aria-haspopup="true"
                                aria-controls="console-gear-panel">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <circle cx="12" cy="12" r="3"/>
                                <path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/>
                            </svg>
                        </button>
                        <div class="console-gear-panel hidden" id="console-gear-panel" role="menu">
                            <div class="console-gear-user" role="presentation">{{ auth()->user()->name }}</div>
                            <a href="{{ route('console.canonical.index') }}" class="console-gear-item" role="menuitem">Canonical databases</a>
                            <form method="POST" action="{{ route('console.logout') }}" class="console-gear-logout" role="none">
                                @csrf
                                <button type="submit" class="console-gear-item console-gear-item-btn" role="menuitem">Log out</button>
                            </form>
                        </div>
                    </div>
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

    <script src="{{ asset('js/console-settings.js') }}?v={{ @filemtime(public_path('js/console-settings.js')) ?: 1 }}" defer></script>
    @stack('scripts')
</body>
</html>
