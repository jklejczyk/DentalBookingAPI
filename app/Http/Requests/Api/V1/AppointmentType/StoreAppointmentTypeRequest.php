<?php

namespace App\Http\Requests\Api\V1\AppointmentType;

use Illuminate\Contracts\Validation\ValidationRule;

class StoreAppointmentTypeRequest extends BaseAppointmentTypeRequest
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
            'data.attributes.name' => 'required|string',
            'data.attributes.description' => 'nullable|sometimes|string',
            'data.attributes.duration_minutes' => 'required|integer',
        ];
    }
}
