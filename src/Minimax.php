<?php

declare(strict_types=1);

namespace Nejcc\Minimax;

use Nejcc\Minimax\Resources\Countries;
use Nejcc\Minimax\Resources\Currencies;
use Nejcc\Minimax\Resources\Customers;
use Nejcc\Minimax\Resources\Generic;
use Nejcc\Minimax\Resources\Invoices;
use Nejcc\Minimax\Resources\Items;
use Nejcc\Minimax\Resources\Orders;
use Nejcc\Minimax\Resources\Orgs;
use Nejcc\Minimax\Resources\ReportTemplates;
use Nejcc\Minimax\Resources\VatRates;

final class Minimax
{
    private int|string|null $orgId;

    public function __construct(
        private Client $client,
        int|string|null $defaultOrgId = null,
    ) {
        $this->orgId = $defaultOrgId;
    }

    /**
     * Set the organisation context for org-scoped resources.
     */
    public function forOrg(int|string $orgId): static
    {
        $clone = clone $this;
        $clone->orgId = $orgId;

        return $clone;
    }

    public function orgs(): Orgs
    {
        return new Orgs($this->client);
    }

    public function customers(): Customers
    {
        return new Customers($this->client, $this->orgId());
    }

    public function items(): Items
    {
        return new Items($this->client, $this->orgId());
    }

    public function invoices(): Invoices
    {
        return new Invoices($this->client, $this->orgId());
    }

    public function orders(): Orders
    {
        return new Orders($this->client, $this->orgId());
    }

    public function vatRates(): VatRates
    {
        return new VatRates($this->client, $this->orgId());
    }

    public function currencies(): Currencies
    {
        return new Currencies($this->client, $this->orgId());
    }

    public function countries(): Countries
    {
        return new Countries($this->client, $this->orgId());
    }

    public function reportTemplates(): ReportTemplates
    {
        return new ReportTemplates($this->client, $this->orgId());
    }

    /**
     * Access any org-scoped endpoint by its slug (e.g. 'journals', 'accounts').
     * Backs the config('minimax.resources') registry and the admin UI.
     */
    public function resource(string $slug): Generic
    {
        return new Generic($this->client, $this->orgId(), $slug);
    }

    public function client(): Client
    {
        return $this->client;
    }

    private function orgId(): int|string
    {
        if ($this->orgId === null || $this->orgId === '') {
            throw new MinimaxException('No Minimax organisation set. Configure MINIMAX_ORG_ID or call forOrg($id).');
        }

        return $this->orgId;
    }
}
