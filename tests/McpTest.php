<?php

declare(strict_types=1);

namespace Nejcc\Minimax\Tests;

use Illuminate\Support\Facades\Http;
use Laravel\Mcp\Server\McpServiceProvider;
use Nejcc\Minimax\Client;
use Nejcc\Minimax\Mcp\MinimaxServer;
use Nejcc\Minimax\Mcp\Tools\FindRecordTool;
use Nejcc\Minimax\Mcp\Tools\ListOrganisationsTool;
use Nejcc\Minimax\Mcp\Tools\ListResourceTool;
use Nejcc\Minimax\Minimax as Manager;
use Nejcc\Minimax\MinimaxServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

/**
 * Exercises the MCP server + tools in fake mode (no real HTTP).
 */
final class McpTest extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        // laravel/mcp's provider wires the Request-argument binding; Testbench
        // does not auto-discover it, so register it alongside ours.
        return [McpServiceProvider::class, MinimaxServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('minimax.localization', 'SI');
        $app['config']->set('minimax.fake', true);
        $app['config']->set('minimax.org_id', 123);
        $app['config']->set('minimax.token_leeway', 30);
    }

    public function test_list_organisations_tool_returns_orgs(): void
    {
        MinimaxServer::tool(ListOrganisationsTool::class, [])
            ->assertOk()
            ->assertSee('Demo d.o.o. (FAKE)');
    }

    public function test_list_resource_tool_returns_rows(): void
    {
        MinimaxServer::tool(ListResourceTool::class, ['resource' => 'customers', 'org_id' => 123])
            ->assertOk()
            ->assertSee('Demo Customer');
    }

    public function test_list_resource_tool_requires_a_slug(): void
    {
        MinimaxServer::tool(ListResourceTool::class, [])->assertHasErrors();
    }

    public function test_list_resource_tool_rejects_a_slug_outside_the_registry(): void
    {
        MinimaxServer::tool(ListResourceTool::class, ['resource' => 'not_in_registry'])
            ->assertHasErrors();
    }

    public function test_find_record_tool_returns_a_record(): void
    {
        MinimaxServer::tool(FindRecordTool::class, ['resource' => 'customers', 'id' => '1', 'org_id' => 123])
            ->assertOk()
            ->assertSee('Demo Customer');
    }

    public function test_tools_advertise_their_input_schema(): void
    {
        $list = (new ListResourceTool)->toArray();
        $this->assertArrayHasKey('resource', $list['inputSchema']['properties']);

        $find = (new FindRecordTool)->toArray();
        $this->assertArrayHasKey('id', $find['inputSchema']['properties']);
    }

    public function test_tools_report_minimax_errors(): void
    {
        // Switch off fake mode and make the API fail, so the tools hit their
        // MinimaxException catch branch and report a clean error to the client.
        config(['minimax.fake' => false]);
        $this->app->forgetInstance(Client::class);
        $this->app->forgetInstance(Manager::class);

        Http::fake([
            '*/oauth20/token' => Http::response(['access_token' => 't', 'expires_in' => 3600]),
            '*' => Http::response(['Message' => 'nope'], 500),
        ]);

        MinimaxServer::tool(ListOrganisationsTool::class, [])->assertHasErrors();
        MinimaxServer::tool(ListResourceTool::class, ['resource' => 'customers'])->assertHasErrors();
        MinimaxServer::tool(FindRecordTool::class, ['resource' => 'customers', 'id' => '1'])->assertHasErrors();
    }
}
