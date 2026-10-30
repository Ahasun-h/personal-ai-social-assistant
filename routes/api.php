<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DraftController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SocialAccountController;
use App\Http\Controllers\TrendController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Auth
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    // Social Accounts & OAuth
    Route::get('/accounts', [SocialAccountController::class, 'index']);
    Route::get('/accounts/oauth/{provider}/redirect', [SocialAccountController::class, 'redirect']);
    Route::post('/accounts/oauth/{provider}/callback', [SocialAccountController::class, 'callback']);
    Route::delete('/accounts/{id}/disconnect', [SocialAccountController::class, 'disconnect']);

    // Trends & Intelligence
    Route::get('/trends/items', [TrendController::class, 'index']);
    Route::patch('/trends/items/{id}/bookmark', [TrendController::class, 'bookmark']);
    Route::patch('/trends/items/{id}/dismiss', [TrendController::class, 'dismiss']);
    Route::post('/trends/sync', [TrendController::class, 'triggerSync']);

    // Drafts & AI Content
    Route::get('/drafts', [DraftController::class, 'index']);
    Route::post('/drafts', [DraftController::class, 'store']);
    Route::get('/drafts/{id}', [DraftController::class, 'show']);
    Route::post('/drafts/{id}/approve', [DraftController::class, 'approve']);
    Route::post('/drafts/{id}/publish-now', [DraftController::class, 'publishNow']);
    Route::post('/drafts/ai/generate', [DraftController::class, 'generateAi']);

    // Media & Studio
    Route::post('/media/upload', [MediaController::class, 'upload']);
    Route::post('/media/ai/generate', [MediaController::class, 'generateAi']);

    // Calendar & Schedules
    Route::get('/calendar/schedules', [CalendarController::class, 'index']);
    Route::patch('/calendar/schedules/{id}/reschedule', [CalendarController::class, 'reschedule']);

    // Inbox & Comments
    Route::get('/inbox/comments', [CommentController::class, 'index']);
    Route::post('/inbox/comments/{id}/reply', [CommentController::class, 'reply']);

    // Analytics & Metrics
    Route::get('/analytics/dashboard', [AnalyticsController::class, 'dashboard']);

    // Settings Vault & Budget
    Route::get('/settings', [SettingController::class, 'index']);
    Route::post('/settings', [SettingController::class, 'update']);
});
