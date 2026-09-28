<?php

namespace App\Services\LeadGeneration\Ai;

class AiRequest
{
    public string $operation;
    public string $systemPrompt;
    public string $userPrompt;
    public ?string $schemaName;
    public ?array $schema;
    public ?string $model;
    public ?int $maxTokens;
    public ?float $temperature;

    public function __construct(
        string $operation,
        string $systemPrompt,
        string $userPrompt,
        ?array $schema = null,
        ?string $schemaName = null,
        ?string $model = null,
        ?int $maxTokens = null,
        ?float $temperature = null
    ) {
        $this->operation = $operation;
        $this->systemPrompt = $systemPrompt;
        $this->userPrompt = $userPrompt;
        $this->schema = $schema;
        $this->schemaName = $schemaName;
        $this->model = $model;
        $this->maxTokens = $maxTokens;
        $this->temperature = $temperature;
    }

    public function withUserPrompt(string $userPrompt): self
    {
        return new self(
            $this->operation,
            $this->systemPrompt,
            $userPrompt,
            $this->schema,
            $this->schemaName,
            $this->model,
            $this->maxTokens,
            $this->temperature
        );
    }
}
