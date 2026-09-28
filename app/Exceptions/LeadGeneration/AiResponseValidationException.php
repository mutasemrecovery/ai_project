<?php

namespace App\Exceptions\LeadGeneration;

use RuntimeException;

class AiResponseValidationException extends RuntimeException
{
    public function __construct(private array $errors, string $message = 'AI response did not match the expected JSON schema.')
    {
        parent::__construct($message);
    }

    public function errors(): array
    {
        return $this->errors;
    }
}
