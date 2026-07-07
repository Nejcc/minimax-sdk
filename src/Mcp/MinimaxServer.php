<?php

declare(strict_types=1);

namespace Nejcc\Minimax\Mcp;

use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Nejcc\Minimax\Mcp\Tools\FindRecordTool;
use Nejcc\Minimax\Mcp\Tools\ListOrganisationsTool;
use Nejcc\Minimax\Mcp\Tools\ListResourceTool;

#[Name('Minimax')]
#[Version('0.1.0')]
#[Instructions(<<<'TXT'
Read-only access to the Minimax accounting API (SI / HR / RS).

Call list-organisations first to discover organisation ids. Then use
list-resource to page through any org-scoped endpoint (customers, items,
orders, issuedinvoices, journals, accounts, warehouses, …) and find-record
to fetch a single record by id. Every call uses the configured default
organisation unless you pass org_id.
TXT)]
final class MinimaxServer extends Server
{
    /**
     * @var array<int, class-string<\Laravel\Mcp\Server\Tool>>
     */
    protected array $tools = [
        ListOrganisationsTool::class,
        ListResourceTool::class,
        FindRecordTool::class,
    ];
}
