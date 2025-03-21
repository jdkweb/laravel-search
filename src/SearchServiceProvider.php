<?php

namespace Jdkweb\Search;

use Illuminate\Support\ServiceProvider;

class SearchServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     * Binding Rdw class into Laravel service container.
     *
     * @return void
     */
    final public function register():void
    {
        $this->app->singleton(Search::class, function ($app) {
            return new Search();
        });

        // Alias
        $this->app->alias(Search::class, 'search');
    }

    /**
     *
     * @return void
     */
    final public function boot():void
    {
        // php artisan vendor:publish --provider="Jdkweb\Search\SearchServiceProvider" --tag="config"
        $this->publishes([
            dirname(__DIR__).'/config/laravel-search.php' => config_path('laravel-search.php'),
        ], 'config');

        // When not published Load config
        if (is_null(config('laravel-search.settings'))) {
            $this->mergeConfigFrom(dirname(__DIR__).'/config/laravel-search.php', 'laravel-search');
        }
    }
}
