<?php

namespace App\Http\Requests\Api\V1\DentistBlockedSlot;

use Illuminate\Contracts\Validation\ValidationRule;

class StoreDentistBlockedSlotRequest extends BaseDentistBlockedSlotRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'data.attributes.reason' => 'nullable|string',
            'data.attributes.start' => 'required|date|after_or_equal:today',
            'data.attributes.end' => 'required|date|after:data.attributes.start',
        ];
    }
}
