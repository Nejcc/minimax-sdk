<?php

declare(strict_types=1);

namespace Nejcc\Minimax\Tests;

use Nejcc\Minimax\MinimaxServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * Exercises the local-only admin UI: provider boot, routes and views.
 * Runs in the "local" environment with fake mode so no real HTTP is sent.
 */
final class AdminUiTest extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [MinimaxServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['env'] = 'local';

        // Use the package's real resource registry so the nav-coverage test
        // walks exactly the links a live install renders. Only override the
        // credentials/mode; mergeConfigFrom() supplies 'resources'.
        $app['config']->set('minimax.localization', 'SI');
        $app['config']->set('minimax.fake', true);
        $app['config']->set('minimax.admin_prefix', 'admin/minimax');
        $app['config']->set('minimax.client_id', 'c');
        $app['config']->set('minimax.client_secret', 's');
        $app['config']->set('minimax.username', 'u');
        $app['config']->set('minimax.password', 'p');
        $app['config']->set('minimax.scope', 'minimax.si');
        $app['config']->set('minimax.org_id', 123);
        $app['config']->set('minimax.token_leeway', 30);
    }

    public function test_dashboard_renders(): void
    {
        $this->get('admin/minimax')
            ->assertOk()
            ->assertSee('FAKE MODE');
    }

    public function test_diagnostics_renders(): void
    {
        $this->get('admin/minimax/diagnostics')->assertOk();
    }

    public function test_every_registered_resource_nav_link_renders(): void
    {
        $slugs = array_keys((array) config('minimax.resources'));

        // The registry ships 13 slugs; guard against it being wiped/empty.
        $this->assertGreaterThanOrEqual(13, count($slugs));

        foreach ($slugs as $slug) {
            $this->get('admin/minimax/resources/'.$slug)
                ->assertOk()
                ->assertSee(config("minimax.resources.{$slug}"));
        }
    }

    public function test_unknown_resource_is_a_404(): void
    {
        $this->get('admin/minimax/resources/nope')->assertNotFound();
    }

    public function test_unknown_admin_page_is_a_404(): void
    {
        $this->get('admin/minimax/does-not-exist')->assertNotFound();
    }
}
