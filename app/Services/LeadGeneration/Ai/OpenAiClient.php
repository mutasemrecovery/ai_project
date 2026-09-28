<?php

namespace App\Services\LeadGeneration\Ai;

use App\Exceptions\LeadGeneration\AiConfigurationException;
use App\Exceptions\LeadGeneration\AiProviderException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

class OpenAiClient implements AiClientInterface
{
    public function generate(AiRequest $request): AiResult
    {
        $apiKey = (string) config('ai.providers.openai.api_key');

        if ($apiKey === '') {
            throw new AiConfigurationException('AI_API_KEY is required for the OpenAI provider.');
        }

        $model = $request->model ?: (string) config('ai.providers.openai.model');
        $payload = $this->payload($request, $model);
        $response = $this->send($payload, $apiKey);

        return new AiResult(
            'openai',
            $model,
            $this->extractText($response),
            $this->extractUsage($response),
            $response
        );
    }

    private function payload(AiRequest $request, string $model): array
    {
        $payload = [
            'model' => $model,
            'instructions' => $request->systemPrompt,
            'input' => $request->userPrompt,
            'max_output_tokens' => $request->maxTokens ?: (int) config('ai.providers.openai.max_tokens'),
        ];

        $temperature = $request->temperature ?? (float) config('ai.providers.openai.temperature');

        if ($temperature >= 0) {
            $payload['temperature'] = $temperature;
        }

        if ($request->schema) {
            $payload['text'] = [
                'format' => [
                    'type' => 'json_schema',
                    'name' => $request->schemaName ?: 'structured_response',
                    'schema' => $request->schema,
                    'strict' => true,
                ],
            ];
        }

        return $payload;
    }

    private function send(array $payload, string $apiKey): array
    {
        $client = new Client([
            'base_uri' => rtrim((string) config('ai.providers.openai.base_url'), '/') . '/',
            'timeout' => (int) config('ai.providers.openai.timeout'),
        ]);

        $retries = max(0, (int) config('ai.providers.openai.retries'));
        $attempt = 0;

        do {
            try {
                $response = $client->post('responses', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $apiKey,
                        'Content-Type' => 'application/json',
                    ],
                    'json' => $payload,
                ]);

                $decoded = json_decode((string) $response->getBody(), true);

                if (! is_array($decoded)) {
                    throw new AiProviderException('OpenAI returned a non-JSON response.', $response->getStatusCode());
                }

                return $decoded;
            } catch (ConnectException $exception) {
                if ($attempt >= $retries) {
                    throw new AiProviderException('OpenAI request timed out or could not connect.', null, $exception);
                }
            } catch (RequestException $exception) {
                $statusCode = $exception->getResponse()?->getStatusCode();

                if (! $this->shouldRetry($statusCode) || $attempt >= $retries) {
                    throw new AiProviderException(
                        'OpenAI request failed' . ($statusCode ? " with status {$statusCode}." : '.'),
                        $statusCode,
                        $exception
                    );
                }
            }

            $attempt++;
            usleep((int) (250000 * $attempt));
        } while ($attempt <= $retries);

        throw new AiProviderException('OpenAI request failed.');
    }

    private function shouldRetry(?int $statusCode): bool
    {
        return $statusCode === 429 || ($statusCode !== null && $statusCode >= 500);
    }

    private function extractText(array $response): string
    {
        if (isset($response['output_text']) && is_string($response['output_text'])) {
            return $response['output_text'];
        }

        $text = '';

        foreach ($response['output'] ?? [] as $output) {
            foreach ($output['content'] ?? [] as $content) {
                if (($content['type'] ?? null) === 'output_text' && isset($content['text'])) {
                    $text .= $content['text'];
                }
            }
        }

        return $text;
    }

    private function extractUsage(array $response): array
    {
        $usage = $response['usage'] ?? [];

        return [
            'input_tokens' => (int) ($usage['input_tokens'] ?? $usage['prompt_tokens'] ?? 0),
            'output_tokens' => (int) ($usage['output_tokens'] ?? $usage['completion_tokens'] ?? 0),
            'total_tokens' => (int) ($usage['total_tokens'] ?? 0),
        ];
    }
}
