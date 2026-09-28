<?php

namespace App\Services\LeadGeneration\Ai;

interface AiClientInterface
{
    public function generate(AiRequest $request): AiResult;
}
