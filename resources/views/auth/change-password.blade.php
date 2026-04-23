@extends('layouts.app')

@section('title', 'Change password — Somalite')

@section('content')
    <div class="auth-shell">
        <div class="card auth-card">
            <h1>Update your password</h1>
            <p class="tagline">
                @if (auth()->user()->must_change_password)
                    You must change your password before continuing.
                @else
                    Change your password.
                @endif
            </p>

            @if ($errors->any())
                <div class="alert error">
                    @foreach ($errors->all() as $err)
                        <div>{{ $err }}</div>
                    @endforeach
                </div>
            @endif

            <form class="form" method="POST" action="{{ route('password.update') }}">
                @csrf
                @if (!auth()->user()->must_change_password)
                    <div class="field">
                        <label for="current_password">Current password</label>
                        <input id="current_password" name="current_password" type="password" required>
                    </div>
                @endif
                <div class="field">
                    <label for="password">New password</label>
                    <input id="password" name="password" type="password"
                           autocomplete="new-password" required minlength="8">
                </div>
                <div class="field">
                    <label for="password_confirmation">Confirm new password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password"
                           autocomplete="new-password" required minlength="8">
                </div>
                <button class="btn" type="submit">Update password</button>
            </form>

            <p class="muted" style="margin-top:16px;">
                Must be at least 8 characters with upper &amp; lower case and a number.
            </p>
        </div>
    </div>
@endsection
