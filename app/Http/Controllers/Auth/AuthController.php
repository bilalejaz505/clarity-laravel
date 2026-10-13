<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Services\EntitlementService;
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
    public function __construct(public EntitlementService $access) {}

    public function form(Request $request, string $mode = 'login'): View
    {
        return view('auth.form', [
            'mode' => $mode,
            'token' => $request->route('token'),
            'email' => $request->query('email'),
            'next' => $this->next($request->query('next')),
        ]);
    }

    private function next(?string $next): string
    {
        return in_array(
            $next,
            [
                '/account',
                '/dashboard',
                '/chats',
                '/contracts',
                '/agreements',
                '/pricing',
            ],
            true,
        )
            ? $next
            : '/dashboard';
    }

    public function login(LoginRequest $request): Response
    {
        if (
            ! Auth::attemptWhen(
                [
                    'email' => strtolower($request->email),
                    'password' => $request->password,
                    'status' => 'active',
                ],
                fn (User $user): bool => ! $this->access->admin($user),
                $request->boolean('remember'),
            )
        ) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Unable to log in. Check your email and password.',
                    'errors' => [
                        'email' => ['Unable to log in. Check your email and password.'],
                    ],
                ], 422);
            }

            return back()
                ->withErrors([
                    'email' => 'Unable to log in. Check your email and password.',
                ])
                ->onlyInput('email');
        }
        $request->session()->regenerate();
        $request
            ->user()
            ->forceFill(['last_seen' => now()])
            ->save();

        $next = $this->next($request->input('next'));
        if ($next === '/dashboard') {
            $next = $this->access->dashboardPath(
                $request->user(),
                $this->access->local($request),
            );
        }

        $redirect = redirect()->intended($next);

        return $request->expectsJson()
            ? response()->json([
                'message' => 'Signed in successfully.',
                'redirect' => $redirect->getTargetUrl(),
            ])
            : $redirect;
    }

    public function register(RegisterRequest $request): Response
    {
        $data = $request->validated();
        $data['email'] = strtolower($data['email']);
        $user = User::create($data);
        event(new Registered($user));
        Auth::login($user);
        $request->session()->regenerate();

        $message = 'Your account is ready. Check your email to verify your address.';

        return $request->expectsJson()
            ? response()->json([
                'message' => $message,
                'redirect' => url('/account'),
            ])
            : redirect('/account')->with('status', $message);
    }

    public function logout(Request $request): Response
    {
        $admin = $this->access->admin($request->user());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $redirect = $admin ? '/admin/login' : '/login';

        return $request->expectsJson()
            ? response()->json([
                'message' => 'You have been logged out.',
                'redirect' => url($redirect),
            ])
            : redirect($redirect);
    }

    public function forgot(ForgotPasswordRequest $request): Response
    {
        Password::sendResetLink(['email' => strtolower($request->email)]);
        $message = 'If an account exists for that email, password reset instructions have been sent.';

        return $request->expectsJson()
            ? response()->json(['message' => $message])
            : back()->with('status', $message);
    }

    public function reset(ResetPasswordRequest $request): Response
    {
        $result = Password::reset(
            $request->only(
                'email',
                'password',
                'password_confirmation',
                'token',
            ),
            function (User $user, string $password) {
                $user
                    ->forceFill([
                        'password' => Hash::make($password),
                        'remember_token' => Str::random(60),
                    ])
                    ->save();
                event(new PasswordReset($user));
            },
        );

        if ($result === Password::PasswordReset) {
            $message = 'Password updated. Please log in.';

            return $request->expectsJson()
                ? response()->json([
                    'message' => $message,
                    'redirect' => url('/login'),
                ])
                : redirect('/login')->with('status', $message);
        }

        return $request->expectsJson()
            ? response()->json([
                'message' => __($result),
                'errors' => ['email' => [__($result)]],
            ], 422)
            : back()->withErrors(['email' => __($result)]);
    }

    public function verify(EmailVerificationRequest $request): Response
    {
        $request->fulfill();

        return redirect('/dashboard')->with('status', 'Email verified.');
    }

    public function resend(Request $request): Response
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        $message = 'A new verification email has been sent.';

        return $request->expectsJson()
            ? response()->json(['message' => $message])
            : back()->with('status', $message);
    }
}
