<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'company_name',
        'website',
        'domain',
        'industry',
        'company_size',
        'country',
        'city',
        'description',
        'public_links',
        'technologies',
        'evidence',
        'analyzed_at',
    ];

    protected $casts = [
        'public_links' => 'array',
        'technologies' => 'array',
        'evidence' => 'array',
        'analyzed_at' => 'datetime',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
