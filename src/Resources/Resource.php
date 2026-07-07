<?php

declare(strict_types=1);

namespace Nejcc\Minimax\Resources;

use Nejcc\Minimax\Client;

/**
 * Generic CRUD wrapper over an org-scoped Minimax endpoint.
 *
 * Subclasses only declare $endpoint (e.g. "issuedinvoices"). All resources
 * take and return plain arrays using Minimax's own field names.
 */
abstract class Resource
{
    /** Endpoint segment under orgs/{org}/, e.g. "customers". */
    protected string $endpoint;

    public function __construct(
        protected Client $client,
        protected int|string $orgId,
    ) {}

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function all(array $query = []): array
    {
        return $this->client->request('GET', $this->path(), null, $query);
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int|string $id): array
    {
        return $this->client->request('GET', $this->path($id));
    }

    /**
     * Look up a single record by its business code, e.g. VAT rate "S".
     *
     * @param  array<string, mixed>  $query
     * @return array<string, mixed>
     */
    public function byCode(string $code, array $query = []): array
    {
        return $this->client->request('GET', $this->path()."/code({$code})", null, $query);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        return $this->client->request('POST', $this->path(), $data);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function update(int|string $id, array $data): array
    {
        return $this->client->request('PUT', $this->path($id), $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(int|string $id): array
    {
        return $this->client->request('DELETE', $this->path($id));
    }

    protected function path(int|string|null $id = null): string
    {
        $path = "orgs/{$this->orgId}/{$this->endpoint}";

        return $id === null ? $path : "{$path}/{$id}";
    }
}
