<?php

namespace App\Http\Requests\LeadGeneration;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLeadStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('admin')->check();
    }

    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([
                    Lead::STATUS_NEW,
                    Lead::STATUS_ANALYZING,
                    Lead::STATUS_QUALIFIED,
                    Lead::STATUS_APPROVED,
                    Lead::STATUS_CONTACTED,
                    Lead::STATUS_REPLIED,
                    Lead::STATUS_INTERESTED,
                    Lead::STATUS_MEETING,
                    Lead::STATUS_PROPOSAL,
                    Lead::STATUS_WON,
                    Lead::STATUS_LOST,
                    Lead::STATUS_IGNORED,
                ]),
            ],
            'priority' => [
                'nullable',
                Rule::in([
                    Lead::PRIORITY_LOW,
                    Lead::PRIORITY_MEDIUM,
                    Lead::PRIORITY_HIGH,
                    Lead::PRIORITY_CRITICAL,
                ]),
            ],
            'do_not_contact' => 'nullable|boolean',
        ];
    }
}
