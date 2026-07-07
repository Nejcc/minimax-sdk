# Minimax SDK

A Laravel SDK for the [Minimax](https://www.minimax.si) accounting API (SI / HR / RS).
Handles OAuth2 auth and token caching, and wraps issued invoices, orders, customers,
items and the code lists behind a small fluent API.

## Requirements

- PHP 8.4+
- Laravel 13

## Install

```bash
composer require nejcc/minimax-sdk
php artisan vendor:publish --tag=minimax-config
```

Add your credentials to `.env`:

```dotenv
MINIMAX_LOCALIZATION=SI
MINIMAX_CLIENT_ID=...
MINIMAX_CLIENT_SECRET=...
MINIMAX_USERNAME=...
MINIMAX_PASSWORD=...
MINIMAX_ORG_ID=123456
```

No credentials yet? Set `MINIMAX_FAKE=true` and everything runs off canned fixtures.

## Usage

```php
use Nejcc\Minimax\Facades\Minimax;

// which orgs can this user see
$orgs = Minimax::orgs()->all();

// customers in the default org
$customers = Minimax::customers()->all()['Rows'];

// switch org for one chain
Minimax::forOrg(654321)->items()->all();

// create a draft invoice, issue it, grab the PDF
$invoice = Minimax::invoices()->create([...]);
$pdf = Minimax::invoices()->pdf($invoice['IssuedInvoiceId'], $invoice['RowVersion']);
```

Every resource shares the same CRUD surface: `all()`, `find()`, `byCode()`,
`create()`, `update()`, `delete()`. Endpoints without a dedicated class are
reachable through `Minimax::resource('journals')->all()`.

## Admin UI

In the `local` environment the package mounts a small dashboard at
`/admin/minimax` — config status, live diagnostics and a resource browser.
Handy for checking your setup before wiring the SDK into anything.

## MCP (AI coding agents)

If `laravel/mcp` is installed, the package registers a local MCP server named
`minimax` with three read-only tools: `list-organisations`, `list-resource`
and `find-record`. Point any MCP client (Laravel Boost, Claude Code, Codex …)
at it:

```bash
php artisan mcp:start minimax
```

Example client entry (`.mcp.json` / Claude Code):

```json
{
  "mcpServers": {
    "minimax": { "command": "php", "args": ["artisan", "mcp:start", "minimax"] }
  }
}
```

Pair it with `MINIMAX_FAKE=true` to let an agent explore the API shape with no
credentials. To also expose the server over HTTP, publish and edit the routes:
`php artisan vendor:publish --tag=minimax-ai-routes`.

## Docs

Open `docs/index.html` in a browser for the full reference.

## Tests

```bash
composer test
```

## License

MIT. See [LICENSE](LICENSE).
