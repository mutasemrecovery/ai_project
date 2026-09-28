<?php

namespace App\Services\LeadGeneration\Discovery;

use App\Models\Campaign;
use App\Models\LeadSource;

interface LeadSourceConnectorInterface
{
    public function discover(LeadSource $source, Campaign $campaign, array $options = []): iterable;
}
