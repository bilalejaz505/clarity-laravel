<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Api\AccountController;
use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\BillingController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\PageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('private')->group(function () {
    Route::get('/', [PageController::class, 'landing']);
    Route::get('/pricing', [PageController::class, 'pricing']);
    Route::get('/demo', [PageController::class, 'demo']);
    Route::get('/api/config', [AccountController::class, 'config']);
    Route::get('/api/auth', fn (Request $r) => response()->json(['authenticated' => (bool) $r->user()]));
    Route::middleware('guest')->group(function () {
        Route::get('/login', fn (Request $r) => app(AuthController::class)->form($r, 'login'))->name('login');
        Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
        Route::get('/register', fn (Request $r) => app(AuthController::class)->form($r, 'register'))->name('register');
        Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
        Route::get('/forgot-password', fn (Request $r) => app(AuthController::class)->form($r, 'forgot'))->name('password.request');
        Route::post('/forgot-password', [AuthController::class, 'forgot'])->middleware('throttle:3,1')->name('password.email');
        Route::get('/reset-password/{token}', fn (Request $r) => app(AuthController::class)->form($r, 'reset'))->name('password.reset');
        Route::post('/reset-password', [AuthController::class, 'reset'])->middleware('throttle:6,1')->name('password.update');
    });
    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/email/verify', fn () => view('auth.verify'))->name('verification.notice');
        Route::get('/email/verify/{id}/{hash}', [AuthController::class, 'verify'])->middleware(['signed', 'throttle:6,1'])->name('verification.verify');
        Route::post('/email/verification-notification', [AuthController::class, 'resend'])->middleware('throttle:3,1')->name('verification.send');
        Route::get('/account', [PageController::class, 'account']);
        Route::get('/api/account', [AccountController::class, 'show']);
        Route::post('/api/account', [AccountController::class, 'trial'])->middleware('throttle:5,1');
        Route::middleware('active')->group(function () {
            Route::get('/chats', [PageController::class, 'workspace']);
            Route::get('/agreements', [PageController::class, 'workspace']);
            Route::get('/api/workspace', [WorkspaceController::class, 'get']);
            Route::put('/api/workspace', [WorkspaceController::class, 'save']);
            Route::delete('/api/workspace', [WorkspaceController::class, 'delete']);
            Route::post('/api/ai', [AiController::class, 'analyze'])->middleware('throttle:30,1');
            Route::post('/api/billing', [BillingController::class, 'action'])->middleware('throttle:10,1');
        });
        Route::middleware('admin')->group(function () {
            Route::get('/admin', [PageController::class, 'admin']);
            Route::get('/api/admin', [AdminController::class, 'index']);
            Route::post('/api/admin', [AdminController::class, 'save']);
            Route::post('/api/connection', [AdminController::class, 'connection'])->middleware('throttle:6,1');
        });
    });
});
Route::post('/api/paypal/webhook', [BillingController::class, 'webhook'])->middleware('throttle:120,1');
