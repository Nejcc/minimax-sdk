<?php

declare(strict_types=1);

namespace Nejcc\Minimax\Resources;

use Nejcc\Minimax\Client;

/**
 * A Resource bound to an arbitrary endpoint slug at runtime, driven by the
 * config('minimax.resources') registry. Use for endpoints without a dedicated
 * class (accounts, journals, warehouses, …).
 */
final class Generic extends Resource
{
    public function __construct(Client $client, int|string $orgId, string $endpoint)
    {
        parent::__construct($client, $orgId);
        $this->endpoint = $endpoint;
    }
}
