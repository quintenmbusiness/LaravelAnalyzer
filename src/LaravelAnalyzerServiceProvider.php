<?php

namespace quintenmbusiness\LaravelAnalyzer;

use Illuminate\Support\ServiceProvider;

class LaravelAnalyzerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');

        $this->loadViewsFrom(
            __DIR__ . '/views',
            'laravel-analyzer'
        );
    }
}
