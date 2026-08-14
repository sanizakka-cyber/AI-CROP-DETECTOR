<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\OtpVerificationController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\VerifyEmailController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store'])
        ->middleware('throttle:6,1');

    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:6,1');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.email');

    Route::get('reset-password', [NewPasswordController::class, 'create'])
        ->name('password.reset.form');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->middleware('throttle:5,1')
        ->name('password.store');

    // OTP Verification — accessible to guests (registration) and during password reset
    Route::get('verify-otp',                 [OtpVerificationController::class, 'show'])          ->name('otp.verify');
    Route::post('verify-otp',                [OtpVerificationController::class, 'verify'])         ->name('otp.verify.post')    ->middleware('throttle:10,1');
    Route::post('verify-otp/resend',         [OtpVerificationController::class, 'resend'])         ->name('otp.resend')         ->middleware('throttle:3,1');
    Route::post('verify-otp/cancel',         [OtpVerificationController::class, 'cancel'])         ->name('otp.cancel');
    Route::post('verify-otp/switch-to-email',[OtpVerificationController::class, 'switchToEmail'])  ->name('otp.switch.email')   ->middleware('throttle:3,1');

    // Device-independent alternative to the OTP page above: the OTP page is gated by
    // server-side session state from the browser that submitted registration, so a user
    // checking their email on a different device/browser can never reach it. This signed
    // link (embedded in the same OTP email) verifies straight from the email itself.
    Route::get('verify-account/{user}', [OtpVerificationController::class, 'verifyByLink'])
        ->middleware(['signed', 'throttle:10,1'])
        ->name('verification.link');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});

// Gracefully handle direct GET navigation to /logout (bookmark, address bar, old cached link).
// We do NOT log out via GET (CSRF risk) — just redirect to the right place.
Route::get('logout', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});
