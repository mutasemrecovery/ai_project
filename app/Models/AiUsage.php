<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'provider',
        'model',
        'operation',
        'input_tokens',
        'output_tokens',
        'total_tokens',
        'estimated_cost',
    ];

    protected $casts = [
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'total_tokens' => 'integer',
        'estimated_cost' => 'decimal:6',
    ];

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
