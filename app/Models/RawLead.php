<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RawLead extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'new';
    public const STATUS_PROCESSED = 'processed';
    public const STATUS_DUPLICATE = 'duplicate';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'lead_source_id',
        'campaign_id',
        'lead_id',
        'source',
        'source_url',
        'company_name',
        'website',
        'email',
        'phone',
        'contact_methods',
        'location',
        'raw_data',
        'discovered_at',
        'processed_at',
        'status',
        'failure_reason',
    ];

    protected $casts = [
        'raw_data' => 'array',
        'contact_methods' => 'array',
        'discovered_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function leadSource(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
