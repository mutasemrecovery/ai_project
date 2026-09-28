<?php

namespace App\Services\LeadGeneration\Ai;

use App\Exceptions\LeadGeneration\AiConfigurationException;

class AiClientFactory
{
    public function make(?string $provider = null): AiClientInterface
    {
        $provider = $provider ?: (string) config('ai.default', 'openai');

        if ($provider === 'openai') {
            return app(OpenAiClient::class);
        }

        if ($provider === 'fake') {
            return app(FakeAiClient::class);
        }

        throw new AiConfigurationException("Unsupported AI provider [{$provider}].");
    }
}
