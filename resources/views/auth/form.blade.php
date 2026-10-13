@extends('layouts.public')
@section('title', $adminLogin ?? false ? 'Admin sign in · Clarity' : 'Account access · Clarity')
@section('content')
    <main class="public-content auth-content">
        @php
            $authIcon =
                $adminLogin ?? false
                    ? 'shield-lock'
                    : [
                        'login' => 'box-arrow-in-right',
                        'register' => 'person-plus',
                        'forgot' => 'key',
                        'reset' => 'key-fill',
                    ][$mode];
        @endphp
        <section class="card auth-card"><span class="auth-icon" aria-hidden="true"><i
                    class="bi bi-{{ $authIcon }}"></i></span><span
                class="eyebrow">{{ $adminLogin ?? false ? 'CLARITY ADMINISTRATION' : 'YOUR CLARITY WORKSPACE' }}</span>
            <h1>{{ $adminLogin ?? false ? 'Admin sign in.' : ['login' => 'Welcome back.', 'register' => 'Create your account.', 'forgot' => 'Reset your password.', 'reset' => 'Choose a new password.'][$mode] }}
            </h1>
            <p class="muted">
                {{ $adminLogin ?? false ? 'Manage your platform, people, plans, and connections.' : ($mode === 'login' ? 'Log in with your email and password to continue.' : ($mode === 'register' ? 'One account for chat insights and freelance contracts.' : 'Use your email to recover access to your workspace.')) }}
            </p>
            <form method="POST"
                action="{{ $adminLogin ?? false ? route('admin.login.submit') : ['login' => '/login', 'register' => '/register', 'forgot' => '/forgot-password', 'reset' => '/reset-password'][$mode] }}"
                class="auth-form">@csrf<input type="hidden" name="next" value="{{ $next }}">
                @if ($mode === 'reset')
                    <input type="hidden" name="token" value="{{ $token }}">
                @endif
                @if ($mode === 'register')
                    <label>Your name<input name="name" value="{{ old('name') }}" autocomplete="name" maxlength="80"
                            required></label>
                @endif
                <label>Email address<input name="email" type="email" value="{{ old('email', $email) }}"
                        autocomplete="email" maxlength="254" required placeholder="you@example.com"></label>
                @if ($mode !== 'forgot')
                    <label>Password<input name="password" type="password"
                            autocomplete="{{ $mode === 'login' ? 'current-password' : 'new-password' }}"
                            minlength="{{ $mode === 'login' ? 1 : 10 }}" maxlength="128" required></label>
                @endif
                @if (in_array($mode, ['register', 'reset']))
                    <label>Confirm password<input name="password_confirmation" type="password" autocomplete="new-password"
                            minlength="10" maxlength="128" required></label>
                @endif
                @if ($adminLogin ?? false)
                    <a href="/login">Back to member login</a>
                @elseif ($mode === 'login')
                    <label class="checkbox-line"><input type="checkbox" name="remember" value="1">Remember me</label><a
                        href="/forgot-password" class="text-btn">Forgot password?</a>
                @endif
                <button class="primary full" type="submit">
                    {{ ['login' => 'Log in', 'register' => 'Create account', 'forgot' => 'Send reset link', 'reset' => 'Save password'][$mode] }}
                    <i class="bi bi-arrow-right" aria-hidden="true"></i>
                </button>
            </form>
            <p class="auth-switch">
                @if ($adminLogin ?? false)
                    Restricted to authorized administrators.
                @elseif ($mode === 'login')
                    New to Clarity?
                    <a href="/register">Create an account</a>
                @else
                    Already have an account? <a href="/login">Log in</a>
                @endif
            </p>
        </section>
    </main>
@endsection
