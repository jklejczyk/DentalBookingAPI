<?php

namespace App\Http\Requests\Api\V1\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class BaseAppointmentRequest extends FormRequest
{
    public function mappedAttributes(): array
    {
        $attributeMap = [
            'data.attributes.status' => 'status',
            'data.attributes.start' => 'start',
            'data.attributes.end' => 'end',
            'data.attributes.appointment_type_id' => 'appointment_type_id',
            'data.attributes.dentist_id' => 'dentist_id',
            'data.attributes.patient_id' => 'patient_id',
        ];

        $attributesToUpdate = [];
        foreach ($attributeMap as $key => $attribute) {
            if ($this->has($key)) {
                $attributesToUpdate[$attribute] = $this->input($key);
            }
        }

        return $attributesToUpdate;
    }

    public function messages(): array
    {
        return [
            'data.attributes.name.required' => 'The name field is required.',
            'data.attributes.name.string' => 'The name must be a string.',
            'data.attributes.description.string' => 'The description must be a string.',
            'data.attributes.duration_minutes.required' => 'The duration minutes field is required.',
            'data.attributes.duration_minutes.integer' => 'The duration minutes must be an integer.',
        ];
    }
}
