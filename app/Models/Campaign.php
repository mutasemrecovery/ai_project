<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'countries',
        'cities',
        'industries',
        'services',
        'keywords',
        'negative_keywords',
        'minimum_score',
        'enabled',
    ];

    protected $casts = [
        'countries' => 'array',
        'cities' => 'array',
        'industries' => 'array',
        'services' => 'array',
        'keywords' => 'array',
        'negative_keywords' => 'array',
        'minimum_score' => 'integer',
        'enabled' => 'boolean',
    ];

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function rawLeads(): HasMany
    {
        return $this->hasMany(RawLead::class);
    }
}
