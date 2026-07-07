<?php

declare(strict_types=1);

namespace Nejcc\Minimax\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Validation\Rule;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Nejcc\Minimax\Minimax;
use Nejcc\Minimax\MinimaxException;

#[Description('List rows from an org-scoped Minimax resource (customers, items, orders, issuedinvoices, journals, accounts, warehouses, …). Returns the raw API rows as JSON.')]
final class ListResourceTool extends Tool
{
    public function handle(Request $request, Minimax $minimax): Response
    {
        $allowed = array_keys((array) config('minimax.resources'));

        $validated = $request->validate([
            'resource' => ['required', 'string', 'max:64', Rule::in($allowed)],
            'org_id' => ['nullable', 'integer'],
        ], [
            'resource.required' => 'Provide a resource slug, e.g. "customers" or "issuedinvoices".',
            'resource.in' => 'Unknown resource. Allowed: '.implode(', ', $allowed).'.',
        ]);

        if (isset($validated['org_id'])) {
            $minimax = $minimax->forOrg($validated['org_id']);
        }

        try {
            $rows = $minimax->resource($validated['resource'])->all()['Rows'] ?? [];
        } catch (MinimaxException $e) {
            return Response::error('Minimax error: '.$e->getMessage());
        }

        return Response::text((string) json_encode($rows, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'resource' => $schema->string()
                ->description('Endpoint slug under orgs/{id}/, e.g. "customers", "issuedinvoices", "journals".')
                ->required(),
            'org_id' => $schema->integer()
                ->description('Organisation id. Defaults to the configured MINIMAX_ORG_ID.'),
        ];
    }
}
