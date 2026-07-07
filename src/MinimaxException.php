<?php

declare(strict_types=1);

namespace Nejcc\Minimax;

use RuntimeException;

final class MinimaxException extends RuntimeException
{
    /**
     * @param  array<string, mixed>|string|null  $body  Decoded response body, when available.
     */
    public function __construct(
        string $message,
        public readonly int $status = 0,
        public readonly array|string|null $body = null,
    ) {
        parent::__construct($message, $status);
    }
}
