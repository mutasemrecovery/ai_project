<?php

namespace App\Services\LeadGeneration;

use App\Models\Lead;
use App\Models\RawLead;
use App\Repositories\LeadGeneration\LeadRepository;

class DuplicateLeadService
{
    public function __construct(
        private LeadRepository $leads,
        private LeadDataNormalizer $normalizer
    ) {
    }

    public function find(array $candidate): ?Lead
    {
        return $this->leads->findPotentialDuplicate(
            $this->normalizer->normalize($candidate)
        );
    }

    public function findForRawLead(RawLead $rawLead): ?Lead
    {
        return $this->find([
            'company_name' => $rawLead->company_name,
            'email' => $rawLead->email,
            'phone' => $rawLead->phone,
            'website' => $rawLead->website,
            'source' => $rawLead->source,
            'source_url' => $rawLead->source_url,
        ]);
    }

    public function merge(Lead $lead, array $candidate): Lead
    {
        return $this->leads->merge($lead, $this->normalizer->normalize($candidate));
    }
}
