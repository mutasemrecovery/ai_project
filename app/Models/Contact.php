<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'name',
        'role',
        'email',
        'phone',
        'linkedin_url',
        'source',
        'confidence',
    ];

    protected $casts = [
        'confidence' => 'decimal:2',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function outreaches(): HasMany
    {
        return $this->hasMany(Outreach::class);
    }
}
