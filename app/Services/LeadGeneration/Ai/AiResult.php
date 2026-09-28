<?php

namespace App\Services\LeadGeneration\Ai;

class AiResult
{
    public string $provider;
    public string $model;
    public string $content;
    public array $usage;
    public array $rawResponse;

    public function __construct(string $provider, string $model, string $content, array $usage = [], array $rawResponse = [])
    {
        $this->provider = $provider;
        $this->model = $model;
        $this->content = $content;
        $this->usage = $usage;
        $this->rawResponse = $rawResponse;
    }
}
