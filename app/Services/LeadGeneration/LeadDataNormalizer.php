<?php

namespace App\Services\LeadGeneration;

use Illuminate\Support\Str;

class LeadDataNormalizer
{
    public function normalize(array $data): array
    {
        $data['company_name'] = $this->text($data['company_name'] ?? null);
        $data['normalized_company_name'] = $this->companyName($data['company_name'] ?? null);
        $data['email'] = $this->email($data['email'] ?? null);
        $data['phone'] = $this->phone($data['phone'] ?? null);
        $data['website'] = $this->url($data['website'] ?? null);
        $data['domain'] = $this->domain($data['domain'] ?? $data['website'] ?? null);
        $data['source_url'] = $this->url($data['source_url'] ?? null);
        $data['country'] = $this->text($data['country'] ?? null);
        $data['city'] = $this->text($data['city'] ?? null);
        $data['industry'] = $this->text($data['industry'] ?? null);

        foreach (['detected_services', 'business_signals', 'contact_methods'] as $key) {
            if (array_key_exists($key, $data)) {
                $data[$key] = $this->arrayValues($data[$key]);
            }
        }

        return $data;
    }

    public function text(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(preg_replace('/\s+/u', ' ', $value) ?: '');

        return $value === '' ? null : $value;
    }

    public function companyName(?string $value): ?string
    {
        $value = $this->text($value);

        if ($value === null) {
            return null;
        }

        $value = Str::lower($value);
        $value = preg_replace('/\b(llc|ltd|limited|inc|co|company|corp|corporation|est|establishment)\b/u', ' ', $value);
        $value = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $value);
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?: '');

        return $value === '' ? null : $value;
    }

    public function email(?string $value): ?string
    {
        $value = $this->text($value);

        return $value ? Str::lower($value) : null;
    }

    public function phone(?string $value): ?string
    {
        $value = $this->text($value);

        if ($value === null) {
            return null;
        }

        $hasInternationalPrefix = str_starts_with($value, '+');
        $digits = preg_replace('/\D+/', '', $value) ?: '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            return '+' . substr($digits, 2);
        }

        return $hasInternationalPrefix ? '+' . $digits : $digits;
    }

    public function url(?string $value): ?string
    {
        $value = $this->text($value);

        if ($value === null) {
            return null;
        }

        if (! preg_match('/^https?:\/\//i', $value)) {
            $value = 'https://' . $value;
        }

        $parts = parse_url($value);

        if (empty($parts['host'])) {
            return null;
        }

        $scheme = Str::lower($parts['scheme'] ?? 'https');
        $host = Str::lower($parts['host']);
        $path = isset($parts['path']) ? rtrim($parts['path'], '/') : '';
        $query = isset($parts['query']) ? '?' . $parts['query'] : '';

        return rtrim("{$scheme}://{$host}{$path}{$query}", '/');
    }

    public function domain(?string $value): ?string
    {
        $url = $this->url($value);

        if ($url === null) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = Str::lower($host);

        return preg_replace('/^www\./i', '', $host) ?: null;
    }

    public function arrayValues(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        $items = is_array($value) ? $value : [$value];
        $items = array_map(fn ($item) => is_string($item) ? $this->text($item) : $item, $items);
        $items = array_filter($items, fn ($item) => $item !== null && $item !== '');

        return array_values(array_unique($items, SORT_REGULAR));
    }
}
