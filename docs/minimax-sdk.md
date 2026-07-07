# Minimax SDK

A fluent Laravel SDK for the **Minimax** accounting API — the cloud accounting platform used across Slovenia (SI), Croatia (HR) and Serbia (RS). It handles OAuth2 authentication and token caching for you, and exposes issued invoices, orders, customers, items and every code list through a small, expressive fluent API.

- **Zero-config auth** — OAuth2 password grant with automatic, leeway-aware token caching.
- **Fake mode** — canned fixtures so the SDK and admin UI run end-to-end before your credentials arrive.
- **Fluent resources** — typed CRUD helpers plus a generic escape hatch for any endpoint.
- **Local admin UI** — a dashboard, live diagnostics and a browsable resource explorer, local env only.

## Requirements

- PHP **8.4+**
- Laravel **13** (`illuminate/support` & `illuminate/http` ^13.0)
- Minimax OAuth2 credentials (from Minimax support) and an application-specific password

> No credentials yet? Skip straight to [Fake mode](#fake-mode) — the whole SDK works offline against canned fixtures.

## Installation

This package lives inside the app as a path repository. Point Composer at it in the root `composer.json`:

```json
"repositories": [
    { "type": "path", "url": "packages/nejcc/minimax-sdk" }
],
"require": {
    "nejcc/minimax-sdk": "@dev"
}
```

Then install and publish the config file:

```bash
composer update nejcc/minimax-sdk

php artisan vendor:publish --tag=minimax-config
```

The service provider and the `Minimax` facade alias are auto-discovered via Laravel package discovery — nothing to register manually.

## Configuration

All configuration is driven by environment variables. Add these to your `.env`:

```dotenv
MINIMAX_LOCALIZATION=SI          # SI | HR | RS
MINIMAX_CLIENT_ID=...            # from Minimax support
MINIMAX_CLIENT_SECRET=...        # from Minimax support
MINIMAX_USERNAME=...             # application-specific password user
MINIMAX_PASSWORD=...             # application-specific password
MINIMAX_SCOPE=minimax.si
MINIMAX_ORG_ID=123456            # default organisation
MINIMAX_FAKE=false               # true = offline fixtures
MINIMAX_TOKEN_LEEWAY=30          # seconds shaved off token TTL
MINIMAX_ADMIN_PREFIX=admin/minimax
```

### Config reference

| Key | Default | Description |
| --- | --- | --- |
| `localization` | `SI` | Country of your account — drives the token & API base URLs. |
| `client_id` / `client_secret` | — | OAuth2 client credentials, provided by Minimax support. |
| `username` / `password` | — | Application-specific password from login.minimax.si/Profile. |
| `scope` | `minimax.si` | OAuth2 scope requested with the token. |
| `org_id` | `null` | Default organisation for org-scoped resources. Override per call with `forOrg()`. |
| `fake` | `false` | Return canned fixtures instead of sending HTTP. See [Fake mode](#fake-mode). |
| `token_leeway` | `30` | Seconds subtracted from `expires_in` so a token never expires mid-flight. |
| `admin_prefix` | `admin/minimax` | URL prefix for the local admin UI. |
| `resources` | 13 slugs | Registry of org-scoped endpoints browsable in the admin UI. See [Generic resources](#generic-resources). |

## Authentication

The SDK authenticates via the OAuth2 **password grant** and caches the access token (keyed by client + user) until shortly before it expires. You never call the token endpoint yourself — every request transparently attaches a valid `Bearer` token.

```php
use Nejcc\Minimax\Facades\Minimax;

// Force a token (usually you never need this — requests do it for you)
$token = Minimax::client()->token();
```

Base URLs are derived from your localization, e.g. for `SI`:

- Token: `https://moj.minimax.si/SI/AUT/oauth20/token`
- API: `https://moj.minimax.si/SI/API/api/…`

> The cache uses your default store. Token TTL is `expires_in − token_leeway`, so with the default 30s leeway you never send a token that dies in transit.

## Fake mode

Set `MINIMAX_FAKE=true` and the SDK sends **no HTTP at all** — it returns canned fixtures instead. This lets you build against the SDK, run the diagnostics page, and browse the resource explorer before real credentials exist.

```php
// .env
MINIMAX_FAKE=true

// Works with no credentials — returns fixture rows
Minimax::forOrg(123456)->customers()->all();
// => ['Rows' => [ ['CustomerId' => 1, 'Name' => 'Demo Customer d.o.o.', ...], ... ]]
```

> Fake mode is a coarse path matcher, not a full API simulator. It stubs the common list/create/issue paths — enough to exercise the SDK. Turn it off once credentials are in place.

## Quick start

Everything goes through the `Minimax` facade. Resources are org-scoped and return plain arrays using Minimax's own field names.

```php
use Nejcc\Minimax\Facades\Minimax;

// List the organisations your user can access
$orgs = Minimax::orgs()->all();

// List customers in the default org (MINIMAX_ORG_ID)
$customers = Minimax::customers()->all()['Rows'];

// Work against a specific org for one chain
$items = Minimax::forOrg(654321)->items()->all();

// Create + issue an invoice, then grab the PDF bytes
$invoice = Minimax::invoices()->create([...]);
$pdf     = Minimax::invoices()->pdf($invoice['IssuedInvoiceId'], $invoice['RowVersion']);
```

## The manager

`Minimax` (resolved via the facade or type-hint) is the entry point. It carries the current organisation context and hands you resource objects.

| Method | Returns | Notes |
| --- | --- | --- |
| `forOrg($orgId)` | `Minimax` | Immutable clone scoped to a different org. |
| `orgs()` | `Orgs` | Not org-scoped. |
| `customers()` | `Customers` | Org-scoped CRUD. |
| `items()` | `Items` | Org-scoped CRUD. |
| `invoices()` | `Invoices` | CRUD + issue/pdf actions. |
| `orders()` | `Orders` | Org-scoped CRUD. |
| `vatRates()` | `VatRates` | Code list. |
| `currencies()` | `Currencies` | Code list. |
| `countries()` | `Countries` | Code list. |
| `reportTemplates()` | `ReportTemplates` | Code list. |
| `resource($slug)` | `Generic` | Any endpoint by slug — see [Generic resources](#generic-resources). |
| `client()` | `Client` | The low-level HTTP client. |

> Org-scoped resources throw `MinimaxException` if no org is set. Configure `MINIMAX_ORG_ID` or call `forOrg($id)` first.

## CRUD basics

Every org-scoped resource (`customers`, `items`, `orders`, `invoices`, code lists and generic slugs) shares the same base CRUD surface. Records are plain arrays with Minimax field names.

| Method | HTTP | Description |
| --- | --- | --- |
| `all($query = [])` | GET | List records; pass query params for paging/filtering. |
| `find($id)` | GET | Fetch one record by id. |
| `byCode($code, $query = [])` | GET | Fetch one record by its business code, e.g. VAT rate "S". |
| `create($data)` | POST | Create; follows the `Location` header and returns the created entity. |
| `update($id, $data)` | PUT | Replace a record. |
| `delete($id)` | DELETE | Delete a record (returns an empty array). |

```php
$rows     = Minimax::customers()->all(['PageSize' => 50])['Rows'];
$customer = Minimax::customers()->find(1);
$created  = Minimax::customers()->create(['Name' => 'ACME d.o.o.', ...]);
Minimax::customers()->update($created['CustomerId'], ['Name' => 'ACME Ltd']);
Minimax::customers()->delete($created['CustomerId']);
```

> **Create follows the Location header.** A `201 Created` response returns the created entity by following the `Location` URL — so `create()` gives you the full record, including its `RowVersion`.

## Organisations

`orgs()` is the only resource that isn't org-scoped — it lives under `currentuser/orgs` and lists every organisation the authenticated user can access. Use it to discover your `MINIMAX_ORG_ID`.

```php
$rows = Minimax::orgs()->all()['Rows'];

foreach ($rows as $row) {
    $row['Organisation']['ID'];   // e.g. 123456
    $row['Organisation']['Name']; // e.g. "Demo d.o.o."
}
```

## Customers, Items & Orders

These are plain CRUD resources — nothing beyond the [base surface](#crud-basics). They map to `customers`, `items` and `orders`.

```php
Minimax::customers()->all();
Minimax::items()->find(77);
Minimax::orders()->create([
    'CustomerId' => 1,
    'Rows'       => [ ['ItemId' => 77, 'Quantity' => 2, 'Price' => 50.00] ],
]);
```

## Code lists

`vatRates()`, `currencies()`, `countries()` and `reportTemplates()` are read-oriented code lists. They support the full CRUD surface, but `byCode()` is the useful one — resolve a record by its business code.

```php
// Resolve the standard 22% VAT rate by its code
$vat = Minimax::vatRates()->byCode('S');
// => ['VatRateId' => 1, 'Percent' => 22, ...]

$eur = Minimax::currencies()->byCode('EUR');
$si  = Minimax::countries()->byCode('SI');
```

## Invoices

`invoices()` maps to `issuedinvoices` and adds three helpers on top of CRUD. Minimax invoices are created in a draft state, then *issued* — a state-changing action guarded by the record's `RowVersion` (optimistic concurrency).

| Method | Description |
| --- | --- |
| `action($id, $action, $rowVersion)` | Run any invoice action (e.g. `"Issue"`, `"IssueAndGeneratePdf"`). |
| `issue($id, $rowVersion)` | Issue the invoice and generate its PDF in one call. Returns the invoice payload. |
| `pdf($id, $rowVersion)` | Issue and return the raw (base64-decoded) PDF bytes as a string. |

```php
// 1. Create a draft invoice
$invoice = Minimax::invoices()->create([
    'CustomerId'  => 1,
    'DateIssued'  => now()->toDateString(),
    'InvoiceRows' => [ ['ItemId' => 77, 'Quantity' => 1, 'Price' => 50.00, 'VatRateId' => 1] ],
]);

// 2. Issue it and stream the PDF to the browser
$bytes = Minimax::invoices()->pdf($invoice['IssuedInvoiceId'], $invoice['RowVersion']);

return response($bytes, 200, [
    'Content-Type'        => 'application/pdf',
    'Content-Disposition' => 'inline; filename="invoice.pdf"',
]);
```

> **Always pass the current `RowVersion`.** It comes back from `create()`/`find()` and guards against issuing a stale invoice. A mismatch is rejected by the API.

## Generic resources

Not every endpoint has a dedicated class. `resource($slug)` binds the full CRUD surface to any org-scoped endpoint at runtime — no new class needed. The admin UI's resource explorer is driven entirely by this plus the `config('minimax.resources')` registry.

```php
Minimax::resource('journals')->all();
Minimax::resource('accounts')->find(1);
Minimax::resource('warehouses')->all()['Rows'];
```

Registered slugs ship in the config's `resources` array. Add a line to expose another endpoint in the admin UI:

```php
// config/minimax.php
'resources' => [
    'issuedinvoices' => 'Issued Invoices',
    'orders'         => 'Orders',
    'customers'      => 'Customers',
    'items'          => 'Items',
    'accounts'       => 'Accounts',
    'journals'       => 'Journals',
    'warehouses'     => 'Warehouses',
    // 'documentnumbering' => 'Document Numbering',  // add your own
],
```

## Error handling

Any failed request — auth or API — throws `Nejcc\Minimax\MinimaxException`. It carries the HTTP status code and the decoded response body so you can inspect what went wrong.

```php
use Nejcc\Minimax\MinimaxException;

try {
    $customer = Minimax::customers()->find(999999);
} catch (MinimaxException $e) {
    $e->getCode();  // HTTP status, e.g. 404
    $e->body;       // decoded response body (array or string)
    report($e);
}
```

## Admin UI

In the `local` environment the package registers a standalone admin section under `admin/minimax` (configurable via `MINIMAX_ADMIN_PREFIX`). It is never loaded in production.

| Route | What it does |
| --- | --- |
| `/admin/minimax` | Dashboard — masked status of every `MINIMAX_*` key and credential readiness. No API calls. |
| `/admin/minimax/diagnostics` | Live connectivity checks: token, organisations, customers. Honours fake mode. |
| `/admin/minimax/resources/{slug}` | Browse any registered resource's first page as a table. |

> Pair the admin UI with [fake mode](#fake-mode) to click through the whole thing before your real credentials land.

## Testing

The SDK uses Laravel's HTTP client, so fake it with `Http::fake()` in your own tests. The package ships 9 tests covering auth, token caching, the Location-follow, invoice PDF decode, `byCode()`, org overrides and error paths.

```php
use Illuminate\Support\Facades\Http;

Http::fake([
    '*/oauth20/token' => Http::response(['access_token' => 'tok-123', 'expires_in' => 3600]),
    '*/orgs/123/customers' => Http::response(['Rows' => [['CustomerId' => 1]]]),
]);

$rows = Minimax::forOrg(123)->customers()->all()['Rows'];

Http::assertSent(fn ($req) => $req->hasHeader('Authorization', 'Bearer tok-123'));
```

Run the package suite:

```bash
cd packages/nejcc/minimax-sdk
../../../vendor/bin/phpunit
```

## API reference

Full facade surface at a glance.

| Call | Signature |
| --- | --- |
| Manager | `forOrg(orgId)`, `client()`, `resource(slug)` |
| Orgs | `orgs()->all()` |
| CRUD (all resources) | `all(query)`, `find(id)`, `byCode(code, query)`, `create(data)`, `update(id, data)`, `delete(id)` |
| Customers | `customers()->…` |
| Items | `items()->…` |
| Orders | `orders()->…` |
| Invoices | `invoices()->…` + `action(id, action, rowVersion)`, `issue(id, rowVersion)`, `pdf(id, rowVersion)` |
| VAT rates | `vatRates()->byCode('S')` |
| Currencies | `currencies()->byCode('EUR')` |
| Countries | `countries()->byCode('SI')` |
| Report templates | `reportTemplates()->…` |
| Generic | `resource('journals')->…` |
| Client | `client()->token()`, `client()->request(method, path, body, query)` |
