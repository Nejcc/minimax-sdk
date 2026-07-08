<?php

return [
    /*
    | Localization of your Minimax account: SI, HR or RS.
    | Drives both the token and API base URLs.
    */
    'localization' => env('MINIMAX_LOCALIZATION', 'SI'),

    /*
    | Fake mode: no HTTP is sent, canned fixtures are returned instead.
    | Use while waiting for real credentials so the SDK and /minimax-test
    | page work end-to-end. Turn off (or unset) once creds are in place.
    */
    'fake' => env('MINIMAX_FAKE', false),

    /*
    | URL prefix for the standalone admin UI (dashboard, diagnostics and the
    | custom 3xx/4xx/5xx error pages). Registered only in the local
    | environment. Change it to move the whole section elsewhere.
    */
    'admin_prefix' => env('MINIMAX_ADMIN_PREFIX', 'admin/minimax'),

    /*
    | Org-scoped resources exposed by the SDK and browsable in the admin UI.
    | slug (exact Minimax endpoint under api/orgs/{id}/) => human label.
    | Slugs verified against the Minimax API Help index. Add a line to expose
    | another endpoint — no new class needed.
    */
    'resources' => [
        'issuedinvoices' => 'Issued Invoices',
        'issuedinvoicepostings' => 'Invoice Postings',
        'orders' => 'Orders',
        'customers' => 'Customers',
        'items' => 'Items',
        'accounts' => 'Accounts',
        'analytics' => 'Analytics',
        'employees' => 'Employees',
        'journals' => 'Journals',
        'stock' => 'Stock',
        'stockentries' => 'Stock Entries',
        'warehouses' => 'Warehouses',
        'documentnumbering' => 'Document Numbering',
    ],

    /*
    | OAuth2 password-grant credentials.
    | client_id / client_secret are provided by Minimax support.
    | username / password come from the "Application-specific passwords"
    | section of your Minimax profile (login.minimax.si/Profile).
    */
    'client_id' => env('MINIMAX_CLIENT_ID'),
    'client_secret' => env('MINIMAX_CLIENT_SECRET'),
    'username' => env('MINIMAX_USERNAME'),
    'password' => env('MINIMAX_PASSWORD'),
    'scope' => env('MINIMAX_SCOPE', 'minimax.si'),

    /*
    | Default organisation ID. If null, resolve it once via
    | Minimax::orgs()->all() and set it here (or per-call with forOrg()).
    */
    'org_id' => env('MINIMAX_ORG_ID'),

    /*
    | Seconds to shave off the token's expires_in when caching, so we never
    | send a token that expires mid-flight.
    */
    'token_leeway' => env('MINIMAX_TOKEN_LEEWAY', 30),

    /*
    | Org-specific record IDs the host app references when building an issued
    | invoice. Minimax wants references, not inline data: a customer, a catalog
    | item and a currency that already exist in the org. For a B2C webshop a
    | single generic customer ("Koncni Kupec") and item are the norm — the real
    | product name is carried per row via ItemName. Resolve these once from the
    | admin UI / API and set them in .env.
    */
    'default_customer_id' => env('MINIMAX_DEFAULT_CUSTOMER_ID'),
    'default_item_id' => env('MINIMAX_DEFAULT_ITEM_ID'),
    'currency_id' => env('MINIMAX_CURRENCY_ID', 7),

    /*
    | Issued-invoice report template id (which layout Minimax renders). Required
    | by the issuedinvoices create endpoint. Find it under report-templates.
    */
    'report_template_id' => env('MINIMAX_REPORT_TEMPLATE_ID'),

    /*
    | Host-app auto-invoicing (optional). When enabled, the application issues a
    | Minimax invoice as soon as an order is paid. This SDK does not act on these
    | keys itself — they drive the host app's integration (e.g. a listener on its
    | own paid-order hook). Off by default. 'auto_invoice_queue' chooses whether
    | that work runs on the queue (recommended) or inline.
    |
    | The host integration is expected to expose extension points, e.g.:
    |   action  minimax.invoice.before_issue ($order)
    |   filter  minimax.invoice.payload      ($payload, $order)
    |   action  minimax.invoice.after_issue  ($order, $created)
    |   action  minimax.invoice.failed       ($order, $exception)
    */
    'auto_invoice' => env('MINIMAX_AUTO_INVOICE', false),
    'auto_invoice_queue' => env('MINIMAX_AUTO_INVOICE_QUEUE', true),
];
