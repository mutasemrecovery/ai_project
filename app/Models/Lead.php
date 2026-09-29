<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lead extends Model
{
    use HasFactory;

    public const STATUS_NEW = 'new';
    public const STATUS_ANALYZING = 'analyzing';
    public const STATUS_QUALIFIED = 'qualified';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_REPLIED = 'replied';
    public const STATUS_INTERESTED = 'interested';
    public const STATUS_MEETING = 'meeting';
    public const STATUS_PROPOSAL = 'proposal';
    public const STATUS_WON = 'won';
    public const STATUS_LOST = 'lost';
    public const STATUS_IGNORED = 'ignored';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_CRITICAL = 'critical';

    protected $fillable = [
        'lead_source_id',
        'campaign_id',
        'icp_id',
        'company_name',
        'normalized_company_name',
        'contact_name',
        'contact_role',
        'email',
        'phone',
        'contact_methods',
        'website',
        'domain',
        'country',
        'city',
        'industry',
        'source',
        'source_url',
        'source_reference',
        'description',
        'company_size',
        'detected_need',
        'detected_services',
        'business_signals',
        'ai_summary',
        'ai_reasoning',
        'lead_score',
        'intent_score',
        'business_fit_score',
        'project_value_score',
        'digital_gap_score',
        'priority',
        'status',
        'do_not_contact',
        'last_contacted_at',
        'next_follow_up_at',
    ];

    protected $casts = [
        'detected_services' => 'array',
        'business_signals' => 'array',
        'contact_methods' => 'array',
        'lead_score' => 'integer',
        'intent_score' => 'integer',
        'business_fit_score' => 'integer',
        'project_value_score' => 'integer',
        'digital_gap_score' => 'integer',
        'do_not_contact' => 'boolean',
        'last_contacted_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
    ];

    public function leadSource(): BelongsTo
    {
        return $this->belongsTo(LeadSource::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function icp(): BelongsTo
    {
        return $this->belongsTo(Icp::class);
    }

    public function rawLeads(): HasMany
    {
        return $this->hasMany(RawLead::class);
    }

    public function companyProfile(): HasOne
    {
        return $this->hasOne(CompanyProfile::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function outreaches(): HasMany
    {
        return $this->hasMany(Outreach::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    public function aiUsages(): HasMany
    {
        return $this->hasMany(AiUsage::class);
    }
}
