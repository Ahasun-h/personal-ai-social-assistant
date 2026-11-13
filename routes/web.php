<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\TrendController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\LandingController;
use Illuminate\Support\Facades\Auth;

// Public Showcase / Landing Website
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return app(LandingController::class)->index();
})->name('home');

Route::get('/landing', [LandingController::class, 'index'])->name('landing');
Route::get('/showcase', [LandingController::class, 'index'])->name('showcase');

// Guest Authentication Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');
});

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox');
    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
    Route::get('/trends', [TrendController::class, 'index'])->name('trends');
    Route::get('/media', [MediaController::class, 'index'])->name('media');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');

    // Notifications Web API
    Route::get('/api/v1/notifications', [NotificationController::class, 'index']);
    Route::post('/api/v1/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
    Route::post('/api/v1/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::delete('/api/v1/notifications/{id}', [NotificationController::class, 'destroy']);

    // Analytics AI Web API
    Route::post('/api/v1/analytics/ai-insights', [AnalyticsController::class, 'generateAiInsights']);

    // Trend Preferences & Radar Web API
    Route::get('/api/v1/trends/preferences', [TrendController::class, 'getPreferences']);
    Route::post('/api/v1/trends/preferences', [TrendController::class, 'savePreferences']);
    Route::post('/api/v1/trends/sync', [TrendController::class, 'triggerSync']);

    // Web AI Provider Endpoints for Settings UI
    Route::get('/api/v1/ai/config', [App\Http\Controllers\Api\AiGenerationController::class, 'getConfig']);
    Route::post('/api/v1/ai/config', [App\Http\Controllers\Api\AiGenerationController::class, 'saveConfig']);
    Route::post('/api/v1/ai/test', [App\Http\Controllers\Api\AiGenerationController::class, 'testConnection']);

    // Web Media Endpoints for Media Studio SPA
    Route::post('/api/v1/media/upload', [App\Http\Controllers\MediaController::class, 'upload']);
    Route::post('/api/v1/media/ai/generate', [App\Http\Controllers\MediaController::class, 'generateAi']);
    Route::post('/api/v1/media/ai/video-generate', [App\Http\Controllers\MediaController::class, 'generateVideoAi']);
    Route::delete('/api/v1/media/{id}', [App\Http\Controllers\MediaController::class, 'destroy']);
});
