<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#4338ca">
    <title>@yield('title', 'Somalite')</title>
    <link rel="preload" href="{{ asset('css/app.css') }}" as="style">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    @auth
        <header class="topbar">
            <div class="brand">
                <span class="dot"></span>
                <span>Somalite</span>
            </div>
            <div class="user">
                <span class="muted">{{ auth()->user()->fullName() }}</span>
                <span class="badge">{{ auth()->user()->role?->name ?? 'No Role' }}</span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn ghost" type="submit">Sign out</button>
                </form>
            </div>
        </header>
    @endauth

    <main class="container">
        @if (session('status'))
            <div class="alert success">{{ session('status') }}</div>
        @endif

        @yield('content')
    </main>

    @stack('scripts')
</body>
</html>
