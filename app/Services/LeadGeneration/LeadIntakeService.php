<?php

namespace App\Services\LeadGeneration;

use App\Models\Lead;
use App\Models\RawLead;
use App\Repositories\LeadGeneration\LeadRepository;
use App\Repositories\LeadGeneration\RawLeadRepository;
use Illuminate\Support\Arr;

class LeadIntakeService
{
    public function __construct(
        private RawLeadRepository $rawLeads,
        private LeadRepository $leads,
        private DuplicateLeadService $duplicates,
        private LeadDataNormalizer $normalizer
    ) {
    }

    public function storeRawLead(array $attributes): RawLead
    {
        $attributes['discovered_at'] = $attributes['discovered_at'] ?? now();
        $attributes['status'] = $attributes['status'] ?? RawLead::STATUS_NEW;

        return $this->rawLeads->create($attributes);
    }

    public function promoteRawLead(RawLead $rawLead): Lead
    {
        $attributes = $this->leadAttributesFromRawLead($rawLead);
        $duplicate = $this->duplicates->find($attributes);

        if ($duplicate) {
            $lead = $this->duplicates->merge($duplicate, $attributes);
            $this->rawLeads->markDuplicate($rawLead, $lead);

            return $lead;
        }

        $lead = $this->leads->create($attributes);
        $this->rawLeads->markProcessed($rawLead, $lead);

        return $lead;
    }

    public function leadAttributesFromRawLead(RawLead $rawLead): array
    {
        $rawData = is_array($rawLead->raw_data) ? $rawLead->raw_data : [];

        return $this->normalizer->normalize([
            'lead_source_id' => $rawLead->lead_source_id,
            'campaign_id' => $rawLead->campaign_id,
            'company_name' => $rawLead->company_name ?: Arr::get($rawData, 'company_name'),
            'website' => $rawLead->website ?: Arr::get($rawData, 'website'),
            'email' => $rawLead->email ?: Arr::get($rawData, 'email'),
            'phone' => $rawLead->phone ?: Arr::get($rawData, 'phone'),
            'country' => Arr::get($rawData, 'country'),
            'city' => Arr::get($rawData, 'city') ?: $rawLead->location,
            'industry' => Arr::get($rawData, 'industry'),
            'source' => $rawLead->source,
            'source_url' => $rawLead->source_url,
            'source_reference' => Arr::get($rawData, 'source_reference'),
            'description' => Arr::get($rawData, 'description'),
            'company_size' => Arr::get($rawData, 'company_size'),
            'status' => Lead::STATUS_NEW,
            'priority' => Lead::PRIORITY_LOW,
        ]);
    }
}
