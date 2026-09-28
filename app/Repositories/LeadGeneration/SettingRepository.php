<?php

namespace App\Repositories\LeadGeneration;

use App\Models\Setting;
use Illuminate\Support\Collection;

class SettingRepository
{
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = Setting::query()->where('key', $key)->first();

        return $setting ? $setting->value : $default;
    }

    public function set(
        string $key,
        mixed $value,
        string $type = 'string',
        string $group = 'general',
        ?string $description = null,
        bool $isPublic = false
    ): Setting {
        return Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'type' => $type,
                'group' => $group,
                'description' => $description,
                'is_public' => $isPublic,
            ]
        );
    }

    public function group(string $group): Collection
    {
        return Setting::query()
            ->where('group', $group)
            ->orderBy('key')
            ->get()
            ->pluck('value', 'key');
    }
}
