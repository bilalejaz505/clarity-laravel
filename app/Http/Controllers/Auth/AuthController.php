<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function form(Request $request, string $mode = 'login'): View
    {
        return view('auth.form', ['mode' => $mode, 'token' => $request->route('token'), 'email' => $request->query('email'), 'next' => $this->next($request->query('next'))]);
    }

    private function next(?string $next): string
    {
        return in_array($next, ['/account', '/admin', '/chats', '/contracts', '/agreements', '/pricing'], true) ? $next : '/account';
    }

    public function login(LoginRequest $request): Response
    {
        if (! Auth::attempt(['email' => strtolower($request->email), 'password' => $request->password, 'status' => 'active'], $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Unable to log in. Check your email and password.'])->onlyInput('email');
        }
        $request->session()->regenerate();
        $request->user()->forceFill(['last_seen' => now()])->save();

        return redirect()->intended($this->next($request->input('next')));
    }

    public function register(RegisterRequest $request): Response
    {
        $data = $request->validated();
        $data['email'] = strtolower($data['email']);
        $user = User::create($data);
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        return redirect('/account')->with('status', 'Your account is ready. Check your email to verify your address.');
    }

    public function logout(Request $request): Response
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    public function forgot(ForgotPasswordRequest $request): Response
    {
        Password::sendResetLink(['email' => strtolower($request->email)]);

        return back()->with('status', 'If an account exists for that email, password reset instructions have been sent.');
    }

    public function reset(ResetPasswordRequest $request): Response
    {
        $result = Password::reset($request->only('email', 'password', 'password_confirmation', 'token'), function (User $user, string $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60)])->save();
            event(new PasswordReset($user));
        });

        return $result === Password::PasswordReset ? redirect('/login')->with('status', 'Password updated. Please log in.') : back()->withErrors(['email' => __($result)]);
    }

    public function verify(EmailVerificationRequest $request): Response
    {
        $request->fulfill();

        return redirect('/account')->with('status', 'Email verified.');
    }

    public function resend(Request $request): Response
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return back()->with('status', 'A new verification email has been sent.');
    }
}
