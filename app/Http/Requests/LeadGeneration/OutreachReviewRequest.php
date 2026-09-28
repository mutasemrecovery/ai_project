<?php

namespace App\Http\Requests\LeadGeneration;

use Illuminate\Foundation\Http\FormRequest;

class OutreachReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'subject' => 'nullable|string|max:255',
            'body' => 'required|string',
            'send_now' => 'nullable|boolean',
        ];
    }
}
