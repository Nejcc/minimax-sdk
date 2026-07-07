<?php

declare(strict_types=1);

namespace Nejcc\Minimax\Resources;

use Nejcc\Minimax\Client;

/**
 * Organisations available to the authenticated user.
 * Not org-scoped: lives under currentuser/orgs.
 */
final class Orgs
{
    public function __construct(private Client $client) {}

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->client->request('GET', 'currentuser/orgs');
    }
}
