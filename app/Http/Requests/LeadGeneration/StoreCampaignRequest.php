<?php

namespace App\Http\Requests\LeadGeneration;

use Illuminate\Foundation\Http\FormRequest;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'countries' => 'nullable|string',
            'cities' => 'nullable|string',
            'industries' => 'nullable|string',
            'services' => 'nullable|string',
            'keywords' => 'nullable|string',
            'negative_keywords' => 'nullable|string',
            'minimum_score' => 'nullable|integer|min:0|max:100',
            'enabled' => 'nullable|boolean',
        ];
    }

    public function campaignData(): array
    {
        return [
            'name' => $this->string('name')->toString(),
            'description' => $this->input('description'),
            'countries' => $this->lines('countries'),
            'cities' => $this->lines('cities'),
            'industries' => $this->lines('industries'),
            'services' => $this->lines('services'),
            'keywords' => $this->lines('keywords'),
            'negative_keywords' => $this->lines('negative_keywords'),
            'minimum_score' => (int) $this->input('minimum_score', 0),
            'enabled' => $this->boolean('enabled'),
        ];
    }

    private function lines(string $key): array
    {
        $value = (string) $this->input($key, '');
        $items = preg_split('/[\r\n,]+/', $value) ?: [];

        return array_values(array_filter(array_map('trim', $items)));
    }
}
