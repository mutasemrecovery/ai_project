<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromptTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'operation',
        'provider',
        'model',
        'system_prompt',
        'user_prompt',
        'response_schema',
        'enabled',
    ];

    protected $casts = [
        'response_schema' => 'array',
        'enabled' => 'boolean',
    ];
}
