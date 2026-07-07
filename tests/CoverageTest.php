<?php

declare(strict_types=1);

namespace Nejcc\Minimax\Tests;

use Illuminate\Support\Facades\Http;
use Nejcc\Minimax\Client;
use Nejcc\Minimax\Facades\Minimax;
use Nejcc\Minimax\Minimax as Manager;
use Nejcc\Minimax\MinimaxException;
use Nejcc\Minimax\Resources\Orgs;
use PHPUnit\Framework\Attributes\DataProvider;

final class CoverageTest extends TestCase
{
    private function fakeToken(): array
    {
        return ['*/oauth20/token' => Http::response(['access_token' => 'tok-123', 'expires_in' => 3600])];
    }

    /**
     * Every typed resource maps to the correct org-scoped endpoint.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function resourceEndpoints(): array
    {
        return [
            'customers' => ['customers', 'orgs/123/customers'],
            'items' => ['items', 'orgs/123/items'],
            'orders' => ['orders', 'orgs/123/orders'],
            'invoices' => ['invoices', 'orgs/123/issuedinvoices'],
            'vatRates' => ['vatRates', 'orgs/123/vatrates'],
            'currencies' => ['currencies', 'orgs/123/currencies'],
            'countries' => ['countries', 'orgs/123/countries'],
            'reportTemplates' => ['reportTemplates', 'orgs/123/report-templates'],
        ];
    }

    #[DataProvider('resourceEndpoints')]
    public function test_each_resource_hits_its_endpoint(string $method, string $expectedPath): void
    {
        Http::fake($this->fakeToken() + ['*' => Http::response(['Rows' => []])]);

        Minimax::{$method}()->all();

        Http::assertSent(fn ($req) => str_contains($req->url(), $expectedPath));
    }

    public function test_find_update_and_delete_use_the_right_verbs(): void
    {
        Http::fake($this->fakeToken() + ['*' => Http::response(['CustomerId' => 5])]);

        Minimax::customers()->find(5);
        Minimax::customers()->update(5, ['Name' => 'X']);
        Minimax::customers()->delete(5);

        Http::assertSent(fn ($req) => $req->method() === 'GET' && str_ends_with($req->url(), 'orgs/123/customers/5'));
        Http::assertSent(fn ($req) => $req->method() === 'PUT' && str_ends_with($req->url(), 'orgs/123/customers/5'));
        Http::assertSent(fn ($req) => $req->method() === 'DELETE' && str_ends_with($req->url(), 'orgs/123/customers/5'));
    }

    public function test_orgs_lists_the_current_user_organisations(): void
    {
        Http::fake($this->fakeToken() + [
            '*/currentuser/orgs' => Http::response(['Rows' => [['Organisation' => ['ID' => 123]]]]),
        ]);

        $rows = Minimax::orgs()->all()['Rows'];

        $this->assertSame(123, $rows[0]['Organisation']['ID']);
        Http::assertSent(fn ($req) => str_contains($req->url(), 'currentuser/orgs'));
    }

    public function test_authentication_failure_throws_with_status_and_body(): void
    {
        Http::fake(['*/oauth20/token' => Http::response(['error' => 'invalid_grant'], 401)]);

        try {
            Minimax::customers()->all();
            $this->fail('Expected MinimaxException.');
        } catch (MinimaxException $e) {
            $this->assertSame(401, $e->status);
            $this->assertSame('invalid_grant', $e->body['error']);
            $this->assertStringContainsString('authentication failed', $e->getMessage());
        }
    }

    public function test_api_failure_carries_the_decoded_body(): void
    {
        Http::fake($this->fakeToken() + [
            '*/customers/1' => Http::response(['Message' => 'Boom'], 500),
        ]);

        try {
            Minimax::customers()->find(1);
            $this->fail('Expected MinimaxException.');
        } catch (MinimaxException $e) {
            $this->assertSame(500, $e->status);
            $this->assertSame('Boom', $e->body['Message']);
        }
    }

    public function test_for_org_is_immutable_and_leaves_the_default_intact(): void
    {
        Http::fake($this->fakeToken() + ['*' => Http::response(['Rows' => []])]);

        $default = app(Manager::class);
        $scoped = $default->forOrg(999);

        $this->assertNotSame($default, $scoped);

        $scoped->items()->all();
        $default->items()->all();

        Http::assertSent(fn ($req) => str_contains($req->url(), 'orgs/999/items'));
        Http::assertSent(fn ($req) => str_contains($req->url(), 'orgs/123/items'));
    }

    public function test_accessors_return_the_underlying_objects(): void
    {
        $this->assertInstanceOf(Client::class, Minimax::client());
        $this->assertInstanceOf(Orgs::class, Minimax::orgs());
    }

    public function test_token_cache_is_scoped_per_localization(): void
    {
        Http::fake([
            '*/SI/AUT/oauth20/token' => Http::response(['access_token' => 'si-tok', 'expires_in' => 3600]),
            '*/HR/AUT/oauth20/token' => Http::response(['access_token' => 'hr-tok', 'expires_in' => 3600]),
        ]);

        $base = [
            'client_id' => 'c', 'client_secret' => 's', 'username' => 'u', 'password' => 'p',
            'scope' => 'minimax.si', 'token_leeway' => 30,
        ];
        $cache = app(\Illuminate\Contracts\Cache\Repository::class);
        $http = app(\Illuminate\Http\Client\Factory::class);

        // Same credentials, different localization → must NOT share a token.
        $si = new Client($http, $cache, ['localization' => 'SI'] + $base);
        $hr = new Client($http, $cache, ['localization' => 'HR'] + $base);

        $this->assertSame('si-tok', $si->token());
        $this->assertSame('hr-tok', $hr->token());
    }
}
