<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUp extends Model
{
    use HasFactory;

    public const STATUS_PENDING_APPROVAL = 'pending_approval';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_SENT = 'sent';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'lead_id',
        'outreach_id',
        'sequence_number',
        'delay_days',
        'scheduled_at',
        'status',
        'subject',
        'body',
        'approved_by',
        'approved_at',
        'sent_at',
    ];

    protected $casts = [
        'sequence_number' => 'integer',
        'delay_days' => 'integer',
        'scheduled_at' => 'datetime',
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function outreach(): BelongsTo
    {
        return $this->belongsTo(Outreach::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'approved_by');
    }
}
