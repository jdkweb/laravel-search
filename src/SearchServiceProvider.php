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
    final public function register(): void
    {
        $this->app->singleton(Search::class, function ($app, $params) {
            $settings = (!empty($params['settings']) ?
                $params['settings'] : (!empty($params[0]) ?
                    $params[0] : config('laravel-search-system.defaultSearchEngineSettings')
                )
            );
            return new Search($settings);
        });

        // Alias
        $this->app->alias(Search::class, 'search');
    }

    /**
     *
     * @return void
     */
    final public function boot(): void
    {
        // php artisan vendor:publish --provider="Jdkweb\Search\SearchServiceProvider" --tag="config"
        $this->publishes([
            dirname(__DIR__).'/config/laravel-search.php' => config_path('laravel-search.php'),
        ], 'config');

        // system core config
        $this->publishes([
            dirname(__DIR__).'/config/laravel-search-system.php' => config_path('laravel-search-system.php'),
        ], 'config');

        // When not published Load config
        if (is_null(config('laravel-search'))) {
            $this->mergeConfigFrom(dirname(__DIR__).'/config/laravel-search.php', 'laravel-search');
        }
        if (is_null(config('laravel-search-system'))) {
            $this->mergeConfigFrom(dirname(__DIR__).'/config/laravel-search-system.php', 'laravel-search-system');
        }

    }
}
