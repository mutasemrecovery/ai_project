<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Outreach extends Model
{
    use HasFactory;

    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';
    public const STATUS_REPLIED = 'replied';

    protected $fillable = [
        'lead_id',
        'contact_id',
        'channel',
        'message_type',
        'subject',
        'body',
        'personalization_evidence',
        'status',
        'approved_by',
        'approved_at',
        'rejected_at',
        'sent_at',
        'failed_at',
        'failure_reason',
    ];

    protected $casts = [
        'personalization_evidence' => 'array',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }
}
