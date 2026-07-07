<?php

declare(strict_types=1);

namespace Nejcc\Minimax\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;
use Nejcc\Minimax\Minimax;
use Nejcc\Minimax\MinimaxException;

#[Description('Fetch a single record from an org-scoped Minimax resource by its id. Returns the record as JSON.')]
final class FindRecordTool extends Tool
{
    public function handle(Request $request, Minimax $minimax): Response
    {
        $validated = $request->validate([
            'resource' => ['required', 'string', 'max:64'],
            'id' => ['required'],
            'org_id' => ['nullable', 'integer'],
        ]);

        if (isset($validated['org_id'])) {
            $minimax = $minimax->forOrg($validated['org_id']);
        }

        try {
            $record = $minimax->resource($validated['resource'])->find($validated['id']);
        } catch (MinimaxException $e) {
            return Response::error('Minimax error: '.$e->getMessage());
        }

        return Response::text((string) json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * @return array<string, \Illuminate\JsonSchema\Types\Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'resource' => $schema->string()
                ->description('Endpoint slug, e.g. "customers", "issuedinvoices".')
                ->required(),
            'id' => $schema->string()
                ->description('The record id to fetch.')
                ->required(),
            'org_id' => $schema->integer()
                ->description('Organisation id. Defaults to the configured MINIMAX_ORG_ID.'),
        ];
    }
}
