<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\CalendarController;
use App\Http\Controllers\TrendController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\SettingsController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index']);
Route::get('/inbox', [InboxController::class, 'index'])->name('inbox');
Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar');
Route::get('/trends', [TrendController::class, 'index'])->name('trends');
Route::get('/media', [MediaController::class, 'index'])->name('media');
Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
