<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

final class SubsidiaryAuthException extends RuntimeException
{
    public function __construct(
        public readonly string $instanceUrl,
    ) {
        parent::__construct('Autentikasi subsidiary gagal.');
    }
}
