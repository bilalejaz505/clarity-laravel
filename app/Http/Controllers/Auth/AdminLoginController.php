<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\EntitlementService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminLoginController extends Controller
{
    public function form(
        Request $request,
        EntitlementService $access,
    ): View|RedirectResponse {
        if ($request->user() && $access->admin($request->user())) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.form', [
            'mode' => 'login',
            'adminLogin' => true,
            'token' => null,
            'email' => null,
            'next' => '/admin',
        ]);
    }

    public function login(
        LoginRequest $request,
        EntitlementService $access,
    ): RedirectResponse {
        $credentials = [
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'status' => 'active',
        ];
        if (
            ! Auth::attemptWhen(
                $credentials,
                fn (User $user): bool => $access->admin($user),
                $request->boolean('remember'),
            )
        ) {
            return back()
                ->withErrors([
                    'email' => 'Unable to sign in to administration. Check your administrator credentials.',
                ])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->forget('url.intended');
        $request
            ->user()
            ->forceFill(['last_seen' => now()])
            ->save();

        return redirect()->route('admin.dashboard');
    }
}
