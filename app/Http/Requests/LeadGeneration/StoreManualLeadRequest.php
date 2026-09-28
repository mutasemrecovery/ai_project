<?php

namespace App\Http\Requests\LeadGeneration;

use Illuminate\Foundation\Http\FormRequest;

class StoreManualLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'campaign_id' => 'nullable|integer|exists:campaigns,id',
            'company_name' => 'required|string|max:255',
            'website' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:100',
            'location' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'industry' => 'nullable|string|max:150',
            'description' => 'nullable|string',
            'source_url' => 'nullable|string|max:1000',
        ];
    }

    public function rawLeadData(int $leadSourceId): array
    {
        return [
            'lead_source_id' => $leadSourceId,
            'campaign_id' => $this->input('campaign_id'),
            'source' => 'Manual Entry',
            'source_url' => $this->input('source_url'),
            'company_name' => $this->input('company_name'),
            'website' => $this->input('website'),
            'email' => $this->input('email'),
            'phone' => $this->input('phone'),
            'location' => $this->input('location'),
            'raw_data' => [
                'country' => $this->input('country'),
                'city' => $this->input('city'),
                'industry' => $this->input('industry'),
                'description' => $this->input('description'),
            ],
        ];
    }
}
