@extends('layouts.app')

@section('title', 'Sign in — Somalite')

@section('content')
    <div class="auth-shell">
        <div class="card auth-card">
            <div class="brand" style="margin-bottom:16px; display:flex; align-items:center; gap:8px;">
                <span style="width:12px;height:12px;border-radius:50%;background:linear-gradient(135deg,#4338ca,#ec4899);display:inline-block;"></span>
                <strong>Somalite</strong>
            </div>
            <h1>Sign in</h1>
            <p class="tagline">Use the credentials issued by your school.</p>

            @if ($errors->any())
                <div class="alert error">
                    @foreach ($errors->all() as $err)
                        <div>{{ $err }}</div>
                    @endforeach
                </div>
            @endif

            <form class="form" method="POST" action="{{ route('login.store') }}" autocomplete="on">
                @csrf
                <div class="field">
                    <label for="username">Username</label>
                    <input id="username" name="username" type="text"
                           value="{{ old('username') }}"
                           placeholder="firstname middlename"
                           autocomplete="username"
                           autocapitalize="none" autocorrect="off" required autofocus>
                </div>
                <div class="field">
                    <label for="password">Password</label>
                    <input id="password" name="password" type="password"
                           autocomplete="current-password" required>
                </div>
                <div class="field">
                    <label style="display:flex; align-items:center; gap:8px; font-weight:400;">
                        <input type="checkbox" name="remember" value="1"> <span>Keep me signed in</span>
                    </label>
                </div>
                <button class="btn" type="submit">Sign in</button>
            </form>

            <p class="muted" style="margin-top:16px;">
                First time? Default password is your LAST NAME in capitals. You will be asked to change it.
            </p>
        </div>
    </div>
@endsection
