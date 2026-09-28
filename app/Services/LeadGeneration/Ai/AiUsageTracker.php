<?php

namespace App\Services\LeadGeneration\Ai;

use App\Models\AiUsage;

class AiUsageTracker
{
    public function record(AiResult $result, string $operation, ?int $leadId = null): AiUsage
    {
        $inputTokens = (int) ($result->usage['input_tokens'] ?? 0);
        $outputTokens = (int) ($result->usage['output_tokens'] ?? 0);
        $totalTokens = (int) ($result->usage['total_tokens'] ?? ($inputTokens + $outputTokens));

        return AiUsage::create([
            'lead_id' => $leadId,
            'provider' => $result->provider,
            'model' => $result->model,
            'operation' => $operation,
            'input_tokens' => $inputTokens,
            'output_tokens' => $outputTokens,
            'total_tokens' => $totalTokens,
            'estimated_cost' => $this->estimatedCost($inputTokens, $outputTokens),
        ]);
    }

    private function estimatedCost(int $inputTokens, int $outputTokens): float
    {
        $inputCost = (float) config('ai.providers.openai.input_cost_per_million_tokens', 0);
        $outputCost = (float) config('ai.providers.openai.output_cost_per_million_tokens', 0);

        return (($inputTokens / 1000000) * $inputCost) + (($outputTokens / 1000000) * $outputCost);
    }
}
