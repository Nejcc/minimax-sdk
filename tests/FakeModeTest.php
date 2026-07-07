<?php

declare(strict_types=1);

namespace Nejcc\Minimax\Tests;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Support\Facades\Http as HttpFacade;
use Nejcc\Minimax\Client;
use Nejcc\Minimax\Minimax as Manager;

final class FakeModeTest extends TestCase
{
    private function manager(): Manager
    {
        $client = new Client(app(Http::class), app(Cache::class), [
            'localization' => 'SI',
            'client_id' => 'c',
            'client_secret' => 's',
            'username' => 'u',
            'password' => 'p',
            'scope' => 'minimax.si',
            'token_leeway' => 30,
            'fake' => true,
        ]);

        return new Manager($client, 123);
    }

    public function test_fake_mode_returns_fixtures_without_any_http(): void
    {
        HttpFacade::fake();
        HttpFacade::preventStrayRequests();

        $m = $this->manager();

        $this->assertSame('fake-token', $m->client()->token());
        $this->assertArrayHasKey('Rows', $m->orgs()->all());
        $this->assertArrayHasKey('Rows', $m->customers()->all());
        $this->assertArrayHasKey('Rows', $m->items()->all());
        $this->assertArrayHasKey('Rows', $m->resource('journals')->all());

        // Invoice issue + PDF decode path.
        $issued = $m->invoices()->issue(9, 'rv-1');
        $this->assertArrayHasKey('Data', $issued);
        $this->assertSame('%PDF-1.4 fake invoice', $m->invoices()->pdf(9, 'rv-1'));

        // Code-list byCode path.
        $this->assertSame(22, $m->vatRates()->byCode('S')['Percent']);

        // Write verbs.
        $this->assertSame(999, $m->customers()->create(['Name' => 'X'])['CustomerId']);
        $this->assertSame([], $m->customers()->delete(1));

        HttpFacade::assertNothingSent();
    }

    public function test_fake_default_arm_covers_unknown_paths(): void
    {
        HttpFacade::fake();
        HttpFacade::preventStrayRequests();

        $rows = $this->manager()->resource('somethingelse')->all()['Rows'];

        $this->assertNotEmpty($rows);
        HttpFacade::assertNothingSent();
    }
}
