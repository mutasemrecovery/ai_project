<?php

namespace App\Services\LeadGeneration\Ai;

class StructuredResponseValidator
{
    public function decode(string $content): array
    {
        $decoded = json_decode(trim($content), true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return [null, ['Response is not valid JSON: ' . json_last_error_msg()]];
        }

        return [$decoded, []];
    }

    public function repairAndDecode(string $content): array
    {
        $json = $this->extractJson($content);

        if ($json === null) {
            return [null, ['Could not locate a JSON object in the AI response.']];
        }

        return $this->decode($json);
    }

    public function validate(array $data, array $schema): array
    {
        return $this->validateValue($data, $schema, '$');
    }

    private function extractJson(string $content): ?string
    {
        $content = trim($content);
        $content = preg_replace('/^```(?:json)?\s*/i', '', $content) ?: $content;
        $content = preg_replace('/\s*```$/', '', $content) ?: $content;

        $objectStart = strpos($content, '{');
        $arrayStart = strpos($content, '[');

        if ($objectStart === false && $arrayStart === false) {
            return null;
        }

        if ($objectStart === false || ($arrayStart !== false && $arrayStart < $objectStart)) {
            return $this->extractBalanced($content, $arrayStart, '[', ']');
        }

        return $this->extractBalanced($content, $objectStart, '{', '}');
    }

    private function extractBalanced(string $content, int $start, string $open, string $close): ?string
    {
        $depth = 0;
        $inString = false;
        $escaped = false;
        $length = strlen($content);

        for ($index = $start; $index < $length; $index++) {
            $char = $content[$index];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === '"') {
                    $inString = false;
                }

                continue;
            }

            if ($char === '"') {
                $inString = true;
                continue;
            }

            if ($char === $open) {
                $depth++;
            } elseif ($char === $close) {
                $depth--;

                if ($depth === 0) {
                    return substr($content, $start, $index - $start + 1);
                }
            }
        }

        return null;
    }

    private function validateValue(mixed $value, array $schema, string $path): array
    {
        $errors = [];

        if (isset($schema['enum']) && ! in_array($value, $schema['enum'], true)) {
            $errors[] = "{$path} must be one of: " . implode(', ', $schema['enum']);
        }

        if (isset($schema['type']) && ! $this->matchesType($value, $schema['type'])) {
            $errors[] = "{$path} must be of type " . $this->typeName($schema['type']);

            return $errors;
        }

        if (($schema['type'] ?? null) === 'object') {
            $errors = array_merge($errors, $this->validateObject($value, $schema, $path));
        }

        if (($schema['type'] ?? null) === 'array' && isset($schema['items']) && is_array($value)) {
            foreach ($value as $index => $item) {
                $errors = array_merge($errors, $this->validateValue($item, $schema['items'], "{$path}[{$index}]"));
            }
        }

        return $errors;
    }

    private function validateObject(mixed $value, array $schema, string $path): array
    {
        if (! is_array($value) || $this->isList($value)) {
            return ["{$path} must be a JSON object."];
        }

        $errors = [];
        $properties = $schema['properties'] ?? [];

        foreach ($schema['required'] ?? [] as $required) {
            if (! array_key_exists($required, $value)) {
                $errors[] = "{$path}.{$required} is required.";
            }
        }

        foreach ($properties as $property => $propertySchema) {
            if (array_key_exists($property, $value)) {
                $errors = array_merge($errors, $this->validateValue($value[$property], $propertySchema, "{$path}.{$property}"));
            }
        }

        if (($schema['additionalProperties'] ?? true) === false) {
            foreach (array_keys($value) as $property) {
                if (! array_key_exists($property, $properties)) {
                    $errors[] = "{$path}.{$property} is not allowed.";
                }
            }
        }

        return $errors;
    }

    private function matchesType(mixed $value, string|array $type): bool
    {
        $types = is_array($type) ? $type : [$type];

        foreach ($types as $item) {
            if ($item === 'null' && $value === null) {
                return true;
            }

            if ($item === 'string' && is_string($value)) {
                return true;
            }

            if ($item === 'boolean' && is_bool($value)) {
                return true;
            }

            if ($item === 'integer' && is_int($value)) {
                return true;
            }

            if ($item === 'number' && (is_int($value) || is_float($value))) {
                return true;
            }

            if ($item === 'object' && is_array($value) && ! $this->isList($value)) {
                return true;
            }

            if ($item === 'array' && is_array($value) && $this->isList($value)) {
                return true;
            }
        }

        return false;
    }

    private function typeName(string|array $type): string
    {
        return is_array($type) ? implode('|', $type) : $type;
    }

    private function isList(array $value): bool
    {
        if (function_exists('array_is_list')) {
            return array_is_list($value);
        }

        return array_keys($value) === range(0, count($value) - 1);
    }
}
