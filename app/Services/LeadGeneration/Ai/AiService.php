<?php

namespace App\Services\LeadGeneration\Ai;

use App\Exceptions\LeadGeneration\AiResponseValidationException;

class AiService
{
    public function __construct(
        private AiClientFactory $clients,
        private StructuredResponseValidator $validator,
        private AiUsageTracker $usageTracker
    ) {
    }

    public function generateJson(
        string $operation,
        string $systemPrompt,
        string $userPrompt,
        array $schema,
        ?int $leadId = null,
        array $options = []
    ): StructuredAiResponse {
        $client = $this->clients->make($options['provider'] ?? null);
        $request = new AiRequest(
            $operation,
            $this->jsonSystemPrompt($systemPrompt),
            $userPrompt,
            $schema,
            $options['schema_name'] ?? $operation,
            $options['model'] ?? null,
            $options['max_tokens'] ?? null,
            $options['temperature'] ?? null
        );

        $lastResult = null;
        $lastErrors = [];

        for ($attempt = 1; $attempt <= 2; $attempt++) {
            $attemptRequest = $attempt === 1
                ? $request
                : $request->withUserPrompt($this->retryPrompt($userPrompt, $lastErrors));

            $lastResult = $client->generate($attemptRequest);
            $this->usageTracker->record($lastResult, $operation, $leadId);

            [$decoded, $decodeErrors] = $this->validator->decode($lastResult->content);
            $lastErrors = $decodeErrors;

            if ($decoded !== null) {
                $lastErrors = $this->validator->validate($decoded, $schema);

                if ($lastErrors === []) {
                    return new StructuredAiResponse($decoded, $lastResult, $attempt);
                }
            }
        }

        if ($lastResult) {
            [$repaired, $repairErrors] = $this->validator->repairAndDecode($lastResult->content);

            if ($repaired !== null) {
                $lastErrors = $this->validator->validate($repaired, $schema);

                if ($lastErrors === []) {
                    return new StructuredAiResponse($repaired, $lastResult, 2, true);
                }
            } else {
                $lastErrors = $repairErrors;
            }
        }

        throw new AiResponseValidationException($lastErrors);
    }

    private function jsonSystemPrompt(string $systemPrompt): string
    {
        return trim($systemPrompt) . "\n\nReturn only valid JSON. Do not include markdown, commentary, or unsupported fields.";
    }

    private function retryPrompt(string $userPrompt, array $errors): string
    {
        return trim($userPrompt)
            . "\n\nThe previous response was invalid. Return JSON only and satisfy these validation errors: "
            . implode('; ', $errors);
    }
}
