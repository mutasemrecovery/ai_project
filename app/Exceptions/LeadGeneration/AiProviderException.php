<?php

namespace App\Exceptions\LeadGeneration;

use RuntimeException;
use Throwable;

class AiProviderException extends RuntimeException
{
    public function __construct(string $message, private ?int $statusCode = null, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public function statusCode(): ?int
    {
        return $this->statusCode;
    }
}
