<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeadSource extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'provider',
        'description',
        'configuration',
        'enabled',
        'last_run_at',
    ];

    protected $casts = [
        'configuration' => 'array',
        'enabled' => 'boolean',
        'last_run_at' => 'datetime',
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
