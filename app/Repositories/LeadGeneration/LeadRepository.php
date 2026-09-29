<?php

namespace App\Repositories\LeadGeneration;

use App\Models\Lead;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class LeadRepository
{
    public function query(array $filters = []): Builder
    {
        return Lead::query()
            ->with(['leadSource', 'campaign', 'icp'])
            ->when($filters['country'] ?? null, fn (Builder $query, string $country) => $query->where('country', $country))
            ->when($filters['city'] ?? null, fn (Builder $query, string $city) => $query->where('city', $city))
            ->when($filters['industry'] ?? null, fn (Builder $query, string $industry) => $query->where('industry', $industry))
            ->when($filters['priority'] ?? null, fn (Builder $query, string $priority) => $query->where('priority', $priority))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['source'] ?? null, fn (Builder $query, string $source) => $query->where('source', $source))
            ->when($filters['campaign_id'] ?? null, fn (Builder $query, int $campaignId) => $query->where('campaign_id', $campaignId))
            ->when($filters['minimum_score'] ?? null, fn (Builder $query, int $score) => $query->where('lead_score', '>=', $score))
            ->when($filters['maximum_score'] ?? null, fn (Builder $query, int $score) => $query->where('lead_score', '<=', $score))
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query->where('company_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('website', 'like', "%{$search}%");
                });
            });
    }

    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->query($filters)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function find(int $id): ?Lead
    {
        return Lead::query()->find($id);
    }

    public function findOrFail(int $id): Lead
    {
        return Lead::query()->findOrFail($id);
    }

    public function create(array $attributes): Lead
    {
        return Lead::create($this->onlyFillable($attributes));
    }

    public function update(Lead $lead, array $attributes): Lead
    {
        $lead->fill($this->onlyFillable($attributes));
        $lead->save();

        return $lead->refresh();
    }

    public function findPotentialDuplicate(array $attributes): ?Lead
    {
        foreach (['email', 'domain', 'phone'] as $field) {
            if (! empty($attributes[$field])) {
                $lead = Lead::query()->where($field, $attributes[$field])->first();

                if ($lead) {
                    return $lead;
                }
            }
        }

        if (! empty($attributes['source_url'])) {
            $lead = Lead::query()->where('source_url', $attributes['source_url'])->first();

            if ($lead) {
                return $lead;
            }
        }

        if (! empty($attributes['normalized_company_name'])) {
            return Lead::query()
                ->where('normalized_company_name', $attributes['normalized_company_name'])
                ->when($attributes['country'] ?? null, fn (Builder $query, string $country) => $query->where('country', $country))
                ->when($attributes['city'] ?? null, fn (Builder $query, string $city) => $query->where('city', $city))
                ->first();
        }

        return null;
    }

    public function merge(Lead $lead, array $attributes): Lead
    {
        $attributes = $this->onlyFillable($attributes);

        foreach ($attributes as $key => $value) {
            if ($value === null || $value === '' || in_array($key, ['status', 'priority'], true)) {
                continue;
            }

            if (in_array($key, ['detected_services', 'business_signals', 'contact_methods'], true)) {
                $lead->{$key} = $this->mergeArrays($lead->{$key}, $value);
                continue;
            }

            if (empty($lead->{$key})) {
                $lead->{$key} = $value;
            }
        }

        $lead->save();

        return $lead->refresh();
    }

    public function markStatus(Lead $lead, string $status): Lead
    {
        $lead->status = $status;
        $lead->save();

        return $lead->refresh();
    }

    private function onlyFillable(array $attributes): array
    {
        return Arr::only($attributes, (new Lead())->getFillable());
    }

    private function mergeArrays(mixed $current, mixed $incoming): array
    {
        $current = is_array($current) ? $current : [];
        $incoming = is_array($incoming) ? $incoming : [];

        return array_values(array_unique(array_merge($current, $incoming), SORT_REGULAR));
    }
}
