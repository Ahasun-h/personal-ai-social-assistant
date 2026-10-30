<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Dashboard');
})->name('dashboard');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
});

Route::get('/inbox', function () {
    return Inertia::render('Inbox/Index');
})->name('inbox');

Route::get('/calendar', function () {
    return Inertia::render('Calendar/Index');
})->name('calendar');

Route::get('/trends', function () {
    return Inertia::render('Trends/Index');
})->name('trends');

Route::get('/media', function () {
    return Inertia::render('Media/Index');
})->name('media');

Route::get('/analytics', function () {
    return Inertia::render('Analytics/Index');
})->name('analytics');

Route::get('/settings', function () {
    return Inertia::render('Settings/Index');
})->name('settings');
