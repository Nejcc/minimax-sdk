<?php

declare(strict_types=1);

namespace Nejcc\Minimax;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Http\Client\Factory as Http;
use Illuminate\Http\Client\Response;

final class Client
{
    /**
     * @param  array{localization: string, client_id: ?string, client_secret: ?string, username: ?string, password: ?string, scope: string, token_leeway: int}  $config
     */
    public function __construct(
        private Http $http,
        private Cache $cache,
        private array $config,
    ) {}

    /**
     * Perform an API request against the Minimax REST API.
     *
     * A path is resolved relative to the localized API base
     * (e.g. "orgs/123/issuedinvoices"). A POST that returns 201 + Location
     * is followed so the created entity is returned, mirroring the API.
     *
     * @param  array<string, mixed>|null  $body
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function request(string $method, string $path, ?array $body = null, array $query = []): array
    {
        // Reject path traversal so a crafted id/slug can't escape the intended
        // endpoint (e.g. "orgs/1/customers/../../foo").
        if (str_contains($path, '..')) {
            throw new MinimaxException("Invalid Minimax API path: {$path}");
        }

        if ($this->config['fake'] ?? false) {
            return $this->fake($method, $path);
        }

        $response = $this->http
            ->withToken($this->token())
            ->acceptJson()
            ->withOptions(['query' => $query])
            ->send($method, $this->baseUrl().'api/'.mb_ltrim($path, '/'), $body === null ? [] : ['json' => $body]);

        if ($response->created() && $location = $response->header('Location')) {
            return $this->follow($location);
        }

        return $this->decode($response);
    }

    /**
     * Follow an absolute Location URL returned by a create call.
     *
     * @return array<string, mixed>
     */
    private function follow(string $url): array
    {
        // Never re-send the Bearer token to a host we didn't authenticate
        // against — only follow a Location on the same origin as the API base.
        $host = mb_strtolower((string) parse_url($url, PHP_URL_HOST));
        $scheme = mb_strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if ($host !== mb_strtolower((string) parse_url($this->baseUrl(), PHP_URL_HOST)) || $scheme !== 'https') {
            throw new MinimaxException("Refusing to follow off-host Location: {$url}");
        }

        return $this->decode(
            $this->http->withToken($this->token())->acceptJson()->get($url)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(Response $response): array
    {
        if ($response->failed()) {
            throw new MinimaxException(
                "Minimax API request failed with status {$response->status()}.",
                $response->status(),
                $response->json() ?? $response->body(),
            );
        }

        // Some actions (e.g. delete) return an empty body.
        return $response->json() ?? [];
    }

    /**
     * Fetch (and cache) an OAuth2 access token via the password grant.
     */
    public function token(): string
    {
        if ($this->config['fake'] ?? false) {
            return 'fake-token';
        }

        $key = 'minimax.token.'.md5(
            $this->loc().'|'.(string) $this->config['client_id'].'|'.(string) $this->config['username']
        );

        if ($cached = $this->cache->get($key)) {
            return $cached;
        }

        $response = $this->http->asForm()->post($this->tokenUrl(), [
            'grant_type' => 'password',
            'client_id' => $this->config['client_id'],
            'client_secret' => $this->config['client_secret'],
            'username' => $this->config['username'],
            'password' => $this->config['password'],
            'scope' => $this->config['scope'],
        ]);

        if ($response->failed()) {
            throw new MinimaxException(
                'Minimax authentication failed.',
                $response->status(),
                $response->json() ?? $response->body(),
            );
        }

        $token = (string) $response->json('access_token');
        $ttl = (int) $response->json('expires_in', 3600) - (int) $this->config['token_leeway'];

        $this->cache->put($key, $token, max(1, $ttl));

        return $token;
    }

    /**
     * Canned fixtures for fake mode — enough to exercise the SDK and the
     * smoke-test page without real credentials.
     *
     * ponytail: coarse path matching, not a real API simulator. Extend the
     * branches if you need to stub more endpoints.
     *
     * @return array<string, mixed>
     */
    private function fake(string $method, string $path): array
    {
        return match (true) {
            str_ends_with($path, 'currentuser/orgs') => ['Rows' => [
                ['Organisation' => ['ID' => 123456, 'Name' => 'Demo d.o.o. (FAKE)']],
            ]],
            str_contains($path, '/actions/IssueAndGeneratePdf') => ['Data' => [
                'AttachmentFileName' => 'invoice.pdf',
                'AttachmentData' => base64_encode('%PDF-1.4 fake invoice'),
            ]],
            str_contains($path, '/code(') => ['VatRateId' => 1, 'Percent' => 22, 'CurrencyId' => 1, 'CountryId' => 1],
            $method === 'POST' => [
                'IssuedInvoiceId' => 999, 'CustomerId' => 999, 'ItemId' => 999, 'OrderId' => 999,
                'RowVersion' => 'fake-rv', 'Status' => 'O',
            ],
            $method === 'DELETE' => [],
            str_contains($path, 'issuedinvoices') => ['Rows' => [
                ['IssuedInvoiceId' => 1001, 'InvoiceNumber' => '2026-0001', 'CustomerName' => 'Demo Customer', 'DateIssued' => '2026-07-01', 'Status' => 'I', 'ValueWithVat' => 122.00],
                ['IssuedInvoiceId' => 1002, 'InvoiceNumber' => '2026-0002', 'CustomerName' => 'Another Buyer', 'DateIssued' => '2026-07-03', 'Status' => 'O', 'ValueWithVat' => 61.00],
            ], 'CurrentPageSize' => 2],
            str_contains($path, 'orders') => ['Rows' => [
                ['OrderId' => 501, 'OrderNumber' => 'N-2026-12', 'CustomerName' => 'Demo Customer', 'Date' => '2026-07-02', 'Status' => 'O'],
            ], 'CurrentPageSize' => 1],
            str_contains($path, 'items') => ['Rows' => [
                ['ItemId' => 77, 'Code' => 'COLL-01', 'Name' => 'Explorer Collection', 'Price' => 50.00, 'ItemType' => 'B'],
                ['ItemId' => 78, 'Code' => 'GIFT-25', 'Name' => 'Gift Card 25€', 'Price' => 25.00, 'ItemType' => 'S'],
            ], 'CurrentPageSize' => 2],
            str_contains($path, 'accounts') => ['Rows' => [
                ['AccountId' => 1, 'Account' => '1200', 'Name' => 'Terjatve do kupcev'],
                ['AccountId' => 2, 'Account' => '2200', 'Name' => 'Obveznosti do dobaviteljev'],
            ], 'CurrentPageSize' => 2],
            str_contains($path, 'employees') => ['Rows' => [
                ['EmployeeId' => 5, 'Name' => 'Janez', 'Surname' => 'Novak'],
            ], 'CurrentPageSize' => 1],
            str_contains($path, 'journals') => ['Rows' => [
                ['JournalId' => 900, 'JournalNumber' => 'TE-2026-1', 'Date' => '2026-07-01', 'Description' => 'Otvoritev'],
            ], 'CurrentPageSize' => 1],
            str_contains($path, 'warehouses') => ['Rows' => [
                ['WarehouseId' => 1, 'Code' => 'GLAVNO', 'Name' => 'Glavno skladišče'],
            ], 'CurrentPageSize' => 1],
            str_contains($path, 'customers') => ['Rows' => [
                ['CustomerId' => 1, 'Code' => 'C-001', 'Name' => 'Demo Customer d.o.o.', 'City' => 'Ljubljana', 'SubjectToVAT' => 'D'],
                ['CustomerId' => 2, 'Code' => 'C-002', 'Name' => 'Another Buyer s.p.', 'City' => 'Maribor', 'SubjectToVAT' => 'N'],
            ], 'CurrentPageSize' => 2],
            default => ['Rows' => [
                ['Id' => 1, 'Name' => 'Demo record (FAKE)'],
                ['Id' => 2, 'Name' => 'Another record (FAKE)'],
            ], 'CurrentPageSize' => 2],
        };
    }

    private function loc(): string
    {
        return mb_strtoupper($this->config['localization']);
    }

    private function baseUrl(): string
    {
        return "https://moj.minimax.{$this->loc()}/{$this->loc()}/API/";
    }

    private function tokenUrl(): string
    {
        return "https://moj.minimax.{$this->loc()}/{$this->loc()}/AUT/oauth20/token";
    }
}
