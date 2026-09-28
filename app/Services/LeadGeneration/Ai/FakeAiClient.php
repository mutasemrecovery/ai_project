<?php

namespace App\Services\LeadGeneration\Ai;

class FakeAiClient implements AiClientInterface
{
    public function generate(AiRequest $request): AiResult
    {
        return new AiResult(
            'fake',
            (string) config('ai.providers.fake.model', 'fake-model'),
            (string) config('ai.providers.fake.response', '{}'),
            [
                'input_tokens' => 0,
                'output_tokens' => 0,
                'total_tokens' => 0,
            ]
        );
    }
}
