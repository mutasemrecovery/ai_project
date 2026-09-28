<?php

namespace App\Services\LeadGeneration\Ai;

class StructuredAiResponse
{
    public array $data;
    public AiResult $result;
    public int $attempts;
    public bool $repaired;

    public function __construct(array $data, AiResult $result, int $attempts, bool $repaired = false)
    {
        $this->data = $data;
        $this->result = $result;
        $this->attempts = $attempts;
        $this->repaired = $repaired;
    }
}
