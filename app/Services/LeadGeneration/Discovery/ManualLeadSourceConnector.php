<?php

namespace App\Services\LeadGeneration\Discovery;

use App\Models\Campaign;
use App\Models\LeadSource;

class ManualLeadSourceConnector implements LeadSourceConnectorInterface
{
    public function discover(LeadSource $source, Campaign $campaign, array $options = []): iterable
    {
        return [];
    }
}
