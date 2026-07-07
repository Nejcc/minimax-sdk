<?php

declare(strict_types=1);

namespace Nejcc\Minimax;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

final class MinimaxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/minimax.php', 'minimax');

        $this->app->singleton(Client::class, function ($app) {
            $config = $app['config']['minimax'];

            return new Client(
                $app->make(Http::class),
                $app->make(Cache::class),
                [
                    'localization' => $config['localization'],
                    'client_id' => $config['client_id'],
                    'client_secret' => $config['client_secret'],
                    'username' => $config['username'],
                    'password' => $config['password'],
                    'scope' => $config['scope'],
                    'token_leeway' => (int) $config['token_leeway'],
                    'fake' => (bool) ($config['fake'] ?? false),
                ],
            );
        });

        $this->app->singleton(Minimax::class, fn ($app) => new Minimax(
            $app->make(Client::class),
            $app['config']['minimax.org_id'],
        ));
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/minimax.php' => $this->app->configPath('minimax.php'),
            ], 'minimax-config');

            $this->publishes([
                __DIR__.'/../routes/ai.php' => $this->app->basePath('routes/minimax-ai.php'),
            ], 'minimax-ai-routes');
        }

        // Register the MCP server so AI coding agents (Laravel Boost, Claude,
        // Codex, …) can read the Minimax API. Optional: only wires up when
        // laravel/mcp is installed. Start it with `php artisan mcp:start minimax`.
        if (class_exists(\Laravel\Mcp\Server::class)) {
            \Laravel\Mcp\Facades\Mcp::local('minimax', \Nejcc\Minimax\Mcp\MinimaxServer::class);
        }

        // Local-only standalone admin UI (dashboard, diagnostics, error pages).
        if ($this->app->environment('local')) {
            $this->loadViewsFrom(__DIR__.'/../resources/views', 'minimax');
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');

            View::composer('minimax::*', function ($view): void {
                $view->with([
                    'loc' => config('minimax.localization'),
                    'fake' => (bool) config('minimax.fake'),
                    'prefix' => mb_trim((string) config('minimax.admin_prefix'), '/'),
                ]);
            });
        }
    }
}
