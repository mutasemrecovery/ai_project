<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Icp extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'industries',
        'countries',
        'cities',
        'company_sizes',
        'services',
        'minimum_score',
        'preferred_signals',
        'excluded_signals',
        'enabled',
    ];

    protected $casts = [
        'industries' => 'array',
        'countries' => 'array',
        'cities' => 'array',
        'company_sizes' => 'array',
        'services' => 'array',
        'minimum_score' => 'integer',
        'preferred_signals' => 'array',
        'excluded_signals' => 'array',
        'enabled' => 'boolean',
    ];

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
