<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Nejcc\Minimax\Facades\Minimax;
use Nejcc\Minimax\MinimaxException;

/*
| Standalone admin UI for the Minimax SDK — dashboard, live diagnostics and
| custom 3xx/4xx/5xx error pages. Registered only in the local environment
| (see MinimaxServiceProvider::boot()). Prefix is configurable via
| config('minimax.admin_prefix').
*/

/** Presence + masked value of every MINIMAX_* config key. */
$envStatus = function (): array {
    $keys = [
        'MINIMAX_LOCALIZATION' => config('minimax.localization'),
        'MINIMAX_CLIENT_ID' => config('minimax.client_id'),
        'MINIMAX_CLIENT_SECRET' => config('minimax.client_secret'),
        'MINIMAX_USERNAME' => config('minimax.username'),
        'MINIMAX_PASSWORD' => config('minimax.password'),
        'MINIMAX_SCOPE' => config('minimax.scope'),
        'MINIMAX_ORG_ID' => config('minimax.org_id'),
    ];
    $secret = ['MINIMAX_CLIENT_SECRET', 'MINIMAX_PASSWORD'];
    $env = [];
    foreach ($keys as $key => $value) {
        $set = filled($value);
        $env[$key] = [
            'set' => $set,
            'value' => !$set ? null : (in_array($key, $secret, true) ? str_repeat('•', 8) : (string) $value),
        ];
    }

    return $env;
};

/** Render the shared error page for a given HTTP status. */
$renderError = function (int $code, string $heading, string $message, mixed $body = null) {
    $home = url(mb_trim(config('minimax.admin_prefix'), '/'));

    return response()->view('minimax::error', compact('code', 'heading', 'message', 'body', 'home'), $code);
};

Route::prefix(config('minimax.admin_prefix'))->group(function () use ($envStatus, $renderError): void {
    // Dashboard — cheap, no API calls.
    Route::get('/', function () use ($envStatus) {
        $env = $envStatus();
        $required = ['MINIMAX_CLIENT_ID', 'MINIMAX_CLIENT_SECRET', 'MINIMAX_USERNAME', 'MINIMAX_PASSWORD'];
        $credsReady = collect($required)->every(fn ($k) => $env[$k]['set']);

        return view('minimax::dashboard', [
            'env' => $env,
            'credsReady' => $credsReady,
            'orgId' => config('minimax.org_id'),
        ]);
    })->name('minimax.dashboard');

    // Diagnostics — live (or fake) connectivity checks.
    Route::get('/diagnostics', function () use ($renderError) {
        $fake = (bool) config('minimax.fake');
        $checks = [];

        try {
            $token = Minimax::client()->token();
            $checks['Authentication'] = ['ok' => true, 'detail' => 'Token acquired: '.mb_substr($token, 0, 10).'…'];
        } catch (MinimaxException $e) {
            return $renderError(500, 'Authentication failed', 'Could not obtain a Minimax token. Check your credentials or enable fake mode.', $e->body);
        }

        try {
            $rows = Minimax::orgs()->all()['Rows'] ?? [];
            $names = array_map(fn ($r) => $r['Organisation']['Name'] ?? $r['Organisation']['ID'] ?? '?', $rows);
            $checks['Organisations'] = ['ok' => true, 'detail' => count($rows).' found: '.implode(', ', $names)];
        } catch (MinimaxException $e) {
            $checks['Organisations'] = ['ok' => false, 'detail' => $e->getMessage(), 'body' => $e->body];
        }

        $orgId = $fake ? 123456 : config('minimax.org_id');
        if ($orgId) {
            $minimax = $fake ? Minimax::forOrg($orgId) : Minimax::getFacadeRoot();
            try {
                $count = count($minimax->customers()->all()['Rows'] ?? []);
                $checks['Customers (org '.$orgId.')'] = ['ok' => true, 'detail' => $count.' on first page'];
            } catch (MinimaxException $e) {
                $checks['Customers (org '.$orgId.')'] = ['ok' => false, 'detail' => $e->getMessage(), 'body' => $e->body];
            }
        } else {
            $checks['Customers'] = ['ok' => null, 'detail' => 'Set MINIMAX_ORG_ID to test org-scoped resources'];
        }

        return view('minimax::diagnostics', ['checks' => $checks, 'fake' => $fake]);
    })->name('minimax.diagnostics');

    // Resource list pages — driven by the config('minimax.resources') registry.
    Route::get('/resources/{resource}', function (string $resource) use ($renderError) {
        $registry = config('minimax.resources');
        if (!isset($registry[$resource])) {
            return $renderError(404, 'Unknown resource', "'{$resource}' is not in the Minimax resource registry.");
        }

        $fake = (bool) config('minimax.fake');
        $orgId = $fake ? 123456 : config('minimax.org_id');

        if (!$orgId) {
            return $renderError(422, 'No organisation', 'Set MINIMAX_ORG_ID (or enable fake mode) to browse org-scoped resources.');
        }

        $minimax = $fake ? Minimax::forOrg($orgId) : Minimax::getFacadeRoot();

        try {
            $rows = $minimax->resource($resource)->all()['Rows'] ?? [];
        } catch (MinimaxException $e) {
            return $renderError(502, 'Minimax request failed', "Could not load {$registry[$resource]}.", $e->body);
        }

        return view('minimax::resource', ['label' => $registry[$resource], 'slug' => $resource, 'rows' => $rows]);
    })->name('minimax.resource');

    // Custom 404 for any unknown page under the admin prefix. Registered
    // last so the specific routes above win; scoped so it never shadows the
    // app's own global fallback.
    Route::get('/{any}', fn () => $renderError(404, 'Page not found', 'That Minimax admin page does not exist.'))
        ->where('any', '.*')
        ->name('minimax.fallback');
});

// Legacy alias.
Route::redirect('/minimax-test', '/'.mb_trim(config('minimax.admin_prefix'), '/'));
