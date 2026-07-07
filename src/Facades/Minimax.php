<?php

declare(strict_types=1);

namespace Nejcc\Minimax\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Nejcc\Minimax\Minimax forOrg(int|string $orgId)
 * @method static \Nejcc\Minimax\Resources\Orgs orgs()
 * @method static \Nejcc\Minimax\Resources\Customers customers()
 * @method static \Nejcc\Minimax\Resources\Items items()
 * @method static \Nejcc\Minimax\Resources\Invoices invoices()
 * @method static \Nejcc\Minimax\Resources\Orders orders()
 * @method static \Nejcc\Minimax\Resources\VatRates vatRates()
 * @method static \Nejcc\Minimax\Resources\Currencies currencies()
 * @method static \Nejcc\Minimax\Resources\Countries countries()
 * @method static \Nejcc\Minimax\Resources\ReportTemplates reportTemplates()
 * @method static \Nejcc\Minimax\Resources\Generic resource(string $slug)
 * @method static \Nejcc\Minimax\Client client()
 *
 * @see \Nejcc\Minimax\Minimax
 */
final class Minimax extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Nejcc\Minimax\Minimax::class;
    }
}
