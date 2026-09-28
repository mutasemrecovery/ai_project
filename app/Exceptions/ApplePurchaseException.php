<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class ApplePurchaseException extends RuntimeException
{
    public function __construct(
        string $message,
        private int $httpStatus = 422,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function httpStatus(): int
    {
        return $this->httpStatus;
    }
}
