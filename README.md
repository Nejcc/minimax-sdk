# Minimax SDK

[![Latest Version](https://img.shields.io/packagist/v/nejcc/minimax-sdk?logo=packagist&logoColor=white)](https://packagist.org/packages/nejcc/minimax-sdk)
[![Downloads](https://img.shields.io/packagist/dt/nejcc/minimax-sdk)](https://packagist.org/packages/nejcc/minimax-sdk)
[![PHP](https://img.shields.io/packagist/php-v/nejcc/minimax-sdk?logo=php&logoColor=white)](composer.json)
[![Laravel](https://img.shields.io/badge/Laravel-13-ff2d20?logo=laravel&logoColor=white)](composer.json)
[![Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen)](tests)
[![License](https://img.shields.io/packagist/l/nejcc/minimax-sdk)](LICENSE)

A Laravel SDK for the [Minimax](https://www.minimax.si) accounting API (SI / HR / RS).
It handles OAuth2 auth and token caching, and wraps issued invoices, orders,
customers, items and the code lists behind a small fluent API.

- **Zero-config auth** — OAuth2 password grant with automatic, leeway-aware token caching.
- **Fake mode** — canned fixtures so the SDK and admin UI run end-to-end before your credentials arrive.
- **Fluent resources** — typed CRUD helpers plus a generic escape hatch for any endpoint.
- **Local admin UI** — dashboard, live diagnostics and a resource browser (local env only).
- **MCP server** — read the API from AI coding agents (Laravel Boost, Claude, Codex).

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

No credentials yet? Set `MINIMAX_FAKE=true` and everything runs off canned
fixtures. See the [Configuration wiki](https://github.com/Nejcc/minimax-sdk/wiki/Configuration)
for every option.

## Usage

```php
use Nejcc\Minimax\Facades\Minimax;

// which orgs can this user see
$orgs = Minimax::orgs()->all();

// customers in the default org
$customers = Minimax::customers()->all()['Rows'];

// switch org for one chain
Minimax::forOrg(654321)->items()->all();

// resolve a VAT rate by its code
$vat = Minimax::vatRates()->byCode('S');

// create a draft invoice, issue it, grab the PDF
$invoice = Minimax::invoices()->create([...]);
$pdf = Minimax::invoices()->pdf($invoice['IssuedInvoiceId'], $invoice['RowVersion']);
```

Every resource shares the same CRUD surface: `all()`, `find()`, `byCode()`,
`create()`, `update()`, `delete()`. Endpoints without a dedicated class are
reachable through `Minimax::resource('journals')->all()`. Full details in the
[Usage](https://github.com/Nejcc/minimax-sdk/wiki/Usage) and
[Invoices](https://github.com/Nejcc/minimax-sdk/wiki/Invoices) wiki pages.

## Admin UI

In the `local` environment the package mounts a small dashboard at
`/admin/minimax` — config status, live diagnostics and a resource browser.
Handy for checking your setup before wiring the SDK into anything.

## MCP (AI coding agents)

If `laravel/mcp` is installed, the package registers a local MCP server named
`minimax` with three read-only tools: `list-organisations`, `list-resource`
and `find-record`. Point your AI coding agent — the MCP **client** (Claude Code,
Codex, Cursor …) — at it:

```bash
php artisan mcp:start minimax
```

> **Laravel Boost?** Boost is a separate MCP *server* (its own dev tools), not a
> client, so it won't absorb these tools. Run the two side by side — add both to
> your agent's config and it sees both toolsets.

Example client config (`.mcp.json` / Claude Code) — `minimax` runs alongside
any other MCP servers, such as Boost:

```json
{
  "mcpServers": {
    "boost":   { "command": "php", "args": ["artisan", "boost:mcp"] },
    "minimax": { "command": "php", "args": ["artisan", "mcp:start", "minimax"] }
  }
}
```

Pair it with `MINIMAX_FAKE=true` to let an agent explore the API shape with no
credentials. To also expose the server over HTTP, publish and edit the routes:
`php artisan vendor:publish --tag=minimax-ai-routes`. More in the
[MCP wiki](https://github.com/Nejcc/minimax-sdk/wiki/MCP).

## Documentation

- **Wiki** — https://github.com/Nejcc/minimax-sdk/wiki
- **Offline** — open `docs/index.html` in a browser for the full reference.

## Testing

```bash
composer test
```

## Security

Please report vulnerabilities privately — see [SECURITY.md](SECURITY.md).

## License

MIT. See [LICENSE](LICENSE).
