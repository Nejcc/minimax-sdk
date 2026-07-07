<?php

declare(strict_types=1);

namespace Nejcc\Minimax\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Nejcc\Minimax\Minimax;
use Nejcc\Minimax\MinimaxException;

#[Description('List the Minimax organisations the configured user can access, with each id and name. Call this first to discover organisation ids.')]
final class ListOrganisationsTool extends Tool
{
    public function handle(Request $request, Minimax $minimax): Response
    {
        try {
            $rows = $minimax->orgs()->all()['Rows'] ?? [];
        } catch (MinimaxException $e) {
            return Response::error('Minimax error: '.$e->getMessage());
        }

        return Response::text((string) json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}
