<?php

declare(strict_types=1);

namespace Nejcc\Minimax\Tests;

use Nejcc\Minimax\MinimaxServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [MinimaxServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('minimax', [
            'localization' => 'SI',
            'client_id' => 'cid',
            'client_secret' => 'secret',
            'username' => 'user',
            'password' => 'pass',
            'scope' => 'minimax.si',
            'org_id' => 123,
            'token_leeway' => 30,
        ]);
    }
}
