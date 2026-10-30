<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\AI\TextGeneratorInterface;
use App\Contracts\AI\MediaGeneratorInterface;
use App\Services\AI\TextGeneratorManager;
use App\Services\AI\FluxMediaGenerator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(TextGeneratorInterface::class, function () {
            return TextGeneratorManager::resolve();
        });

        $this->app->bind(MediaGeneratorInterface::class, function () {
            return new FluxMediaGenerator();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

