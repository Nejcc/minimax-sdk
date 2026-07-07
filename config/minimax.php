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
];
