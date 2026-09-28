<?php

namespace App\Services\LeadGeneration;

use App\Repositories\LeadGeneration\SettingRepository;
use Illuminate\Support\Collection;

class SettingsService
{
    public function __construct(private SettingRepository $settings)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->settings->get($key, $default);
    }

    public function set(
        string $key,
        mixed $value,
        string $type = 'string',
        string $group = 'general',
        ?string $description = null,
        bool $isPublic = false
    ): void {
        $this->settings->set($key, $value, $type, $group, $description, $isPublic);
    }

    public function group(string $group): Collection
    {
        return $this->settings->group($group);
    }
}
