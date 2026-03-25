<?php

namespace App\Http\Requests\Api\V1\Appointment;

use App\Http\Enums\AppointmentStatusEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StoreAppointmentRequest extends BaseAppointmentRequest
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
            'data.attributes.status' => ['required', 'string', Rule::in(array_column(AppointmentStatusEnum::cases(), 'value'))],
            'data.attributes.appointment_type_id' => 'required|integer',
            'data.attributes.dentist_id' => 'required|integer',
            'data.attributes.patient_id' => 'required|integer',
            'data.attributes.start' => 'required|date',
            'data.attributes.end' => 'required|date|after:data.attributes.start',
        ];
    }
}
