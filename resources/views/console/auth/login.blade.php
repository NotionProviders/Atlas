<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login — Atlas Console</title>
    <link rel="stylesheet" href="{{ asset('css/console.css') }}?v={{ @filemtime(public_path('css/console.css')) ?: 1 }}">
</head>
<body class="console-body console-auth-body">
    <div class="console-auth-card">
        <h1>Atlas Console</h1>
        <p class="console-muted">Sign in to manage client workspace maps.</p>

        @if (session('status'))
            <div class="console-flash">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('console.login') }}" class="console-form">
            @csrf
            <label>
                <span>Email</span>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
                @error('email')<span class="console-field-error">{{ $message }}</span>@enderror
            </label>
            <label>
                <span>Password</span>
                <input type="password" name="password" required autocomplete="current-password">
                @error('password')<span class="console-field-error">{{ $message }}</span>@enderror
            </label>
            <label class="console-checkbox">
                <input type="checkbox" name="remember"> Remember me
            </label>
            <button type="submit" class="console-btn console-btn-primary console-btn-block">Log in</button>
        </form>

        @if (Route::has('console.password.request'))
            <p class="console-auth-footer">
                <a href="{{ route('console.password.request') }}">Forgot password?</a>
            </p>
        @endif
    </div>
</body>
</html>
