<?php

use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\DraftController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SocialAccountController;
use App\Http\Controllers\TrendController;
use App\Http\Controllers\Api\AiGenerationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:60,1')->group(function () {
    // Public Endpoints
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::get('/accounts/oauth/{provider}/redirect', [SocialAccountController::class, 'redirect']);
    Route::post('/accounts/oauth/{provider}/callback', [SocialAccountController::class, 'callback']);

    // Authenticated API Resources (Protected by Sanctum & Web Session)
    Route::middleware('auth:sanctum,web')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        // Social Accounts
        Route::get('/accounts', [SocialAccountController::class, 'index']);
        Route::post('/accounts', [SocialAccountController::class, 'store']);
        Route::post('/accounts/clear-demo', [SocialAccountController::class, 'clearDemoAccounts']);
        Route::delete('/accounts/{id}/disconnect', [SocialAccountController::class, 'disconnect']);

        // Trends & Intelligence
        Route::get('/trends/items', [TrendController::class, 'index']);
        Route::match(['post', 'patch'], '/trends/items/{id}/bookmark', [TrendController::class, 'bookmark']);
        Route::match(['post', 'patch'], '/trends/items/{id}/dismiss', [TrendController::class, 'dismiss']);
        Route::post('/trends/sync', [TrendController::class, 'triggerSync']);
        Route::get('/trends/preferences', [TrendController::class, 'getPreferences']);
        Route::post('/trends/preferences', [TrendController::class, 'savePreferences']);

        // Drafts & AI Content
        Route::get('/drafts', [DraftController::class, 'index']);
        Route::post('/drafts', [DraftController::class, 'store']);
        Route::get('/drafts/{id}', [DraftController::class, 'show']);
        Route::put('/drafts/{id}', [DraftController::class, 'update']);
        Route::post('/drafts/{id}/approve', [DraftController::class, 'approve']);
        Route::post('/drafts/{id}/publish-now', [DraftController::class, 'publishNow']);
        Route::post('/drafts/ai/generate', [DraftController::class, 'generateAi']);

        // Media & Studio
        Route::get('/media', [MediaController::class, 'index']);
        Route::post('/media/upload', [MediaController::class, 'upload']);
        Route::delete('/media/{id}', [MediaController::class, 'destroy']);
        Route::post('/media/ai/generate', [MediaController::class, 'generateAi']);
        Route::post('/media/ai/video-generate', [MediaController::class, 'generateVideoAi']);

        // Calendar & Schedules
        Route::get('/calendar/schedules', [CalendarController::class, 'index']);
        Route::patch('/calendar/schedules/{id}/reschedule', [CalendarController::class, 'reschedule']);

        // Inbox & Comments
        Route::get('/inbox/comments', [CommentController::class, 'index']);
        Route::post('/inbox/comments/{id}/reply', [CommentController::class, 'reply']);

        // Analytics & Metrics
        Route::get('/analytics/dashboard', [AnalyticsController::class, 'dashboard']);
        Route::post('/analytics/ai-insights', [AnalyticsController::class, 'generateAiInsights']);

        // Audit Logs
        Route::get('/audits', [AuditController::class, 'index']);

        // Notifications
        Route::get('/notifications', [NotificationController::class, 'index']);
        Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/notifications/{id}', [NotificationController::class, 'destroy']);

        // Settings Vault & Budget
        Route::get('/settings', [SettingController::class, 'index']);
        Route::post('/settings', [SettingController::class, 'update']);

        // AI Provider & Custom Model Vault (Gemini, Z AI, Claude, OpenAI, Custom)
        Route::get('/ai/config', [AiGenerationController::class, 'getConfig']);
        Route::post('/ai/config', [AiGenerationController::class, 'saveConfig']);
        Route::post('/ai/test', [AiGenerationController::class, 'testConnection']);
        Route::post('/ai/generate', [AiGenerationController::class, 'generateDraft']);
    });
});
