<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;
use Throwable;

final class SubsidiaryUnavailableException extends RuntimeException
{
    public function __construct(
        public readonly string $instanceUrl,
        ?Throwable $previous = null,
    ) {
        parent::__construct('Subsidiary tidak tersedia.', 0, $previous);
    }
}
