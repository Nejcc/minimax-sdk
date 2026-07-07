<?php

declare(strict_types=1);

namespace Nejcc\Minimax\Tests;

use Illuminate\Support\Facades\Http;
use Nejcc\Minimax\Facades\Minimax;
use Nejcc\Minimax\MinimaxException;

final class MinimaxTest extends TestCase
{
    private function fakeToken(): array
    {
        return ['*/oauth20/token' => Http::response([
            'access_token' => 'tok-123',
            'expires_in' => 3600,
        ])];
    }

    public function test_it_authenticates_and_lists_a_resource(): void
    {
        Http::fake($this->fakeToken() + [
            '*/orgs/123/customers' => Http::response(['Rows' => [['CustomerId' => 1]]]),
        ]);

        $result = Minimax::customers()->all();

        $this->assertSame(1, $result['Rows'][0]['CustomerId']);
        Http::assertSent(fn ($req) => $req->hasHeader('Authorization', 'Bearer tok-123')
            && str_contains($req->url(), 'orgs/123/customers'));
    }

    public function test_it_caches_the_token_across_calls(): void
    {
        Http::fake($this->fakeToken() + [
            '*/customers' => Http::response(['Rows' => []]),
        ]);

        Minimax::customers()->all();
        Minimax::customers()->all();

        Http::assertSentCount(3); // 1 token + 2 list calls, not 4
    }

    public function test_it_follows_the_location_header_on_create(): void
    {
        Http::fake($this->fakeToken() + [
            '*/orgs/123/customers/55' => Http::response(['CustomerId' => 55, 'Name' => 'Test']),
            '*/orgs/123/customers' => Http::response('', 201, [
                'Location' => 'https://moj.minimax.SI/SI/API/api/orgs/123/customers/55',
            ]),
        ]);

        $result = Minimax::customers()->create(['Name' => 'Test']);

        $this->assertSame(55, $result['CustomerId']);
    }

    public function test_it_issues_an_invoice_and_decodes_the_pdf(): void
    {
        Http::fake($this->fakeToken() + [
            '*/issuedinvoices/9/actions/IssueAndGeneratePdf*' => Http::response([
                'Data' => ['AttachmentData' => base64_encode('%PDF-1.4 fake')],
            ]),
        ]);

        $pdf = Minimax::invoices()->pdf(9, 'rv-1');

        $this->assertSame('%PDF-1.4 fake', $pdf);
        Http::assertSent(fn ($req) => str_contains($req->url(), 'rowVersion=rv-1'));
    }

    public function test_it_resolves_a_vat_rate_by_code(): void
    {
        Http::fake($this->fakeToken() + [
            '*/vatrates/code(S)*' => Http::response(['VatRateId' => 7, 'Percent' => 22]),
        ]);

        $result = Minimax::vatRates()->byCode('S', ['date' => '2026-07-06']);

        $this->assertSame(7, $result['VatRateId']);
    }

    public function test_for_org_overrides_the_default_org(): void
    {
        Http::fake($this->fakeToken() + [
            '*/orgs/999/items' => Http::response(['Rows' => []]),
        ]);

        Minimax::forOrg(999)->items()->all();

        Http::assertSent(fn ($req) => str_contains($req->url(), 'orgs/999/items'));
    }

    public function test_generic_resource_hits_the_slug_endpoint(): void
    {
        Http::fake($this->fakeToken() + [
            '*/orgs/123/journals' => Http::response(['Rows' => [['JournalId' => 900]]]),
        ]);

        $result = Minimax::resource('journals')->all();

        $this->assertSame(900, $result['Rows'][0]['JournalId']);
        Http::assertSent(fn ($req) => str_contains($req->url(), 'orgs/123/journals'));
    }

    public function test_it_throws_on_a_failed_request(): void
    {
        Http::fake($this->fakeToken() + [
            '*/customers/404' => Http::response(['Message' => 'Not found'], 404),
        ]);

        $this->expectException(MinimaxException::class);

        Minimax::customers()->find(404);
    }

    public function test_it_throws_when_no_org_is_configured(): void
    {
        $minimax = new \Nejcc\Minimax\Minimax(app(\Nejcc\Minimax\Client::class), null);

        $this->expectException(MinimaxException::class);
        $this->expectExceptionMessage('No Minimax organisation set');

        $minimax->customers()->all();
    }
}
