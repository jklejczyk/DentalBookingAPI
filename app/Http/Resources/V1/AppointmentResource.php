<?php

namespace App\Http\Resources\V1;

use App\Models\Appointment;
use App\Models\AppointmentType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Appointment */
class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'appointment',
            'id' => $this->id,
            'attributes' => [
                'start' => $this->start,
                'end' => $this->end,
                'status' => $this->status,
                'patient_id' => $this->patient_id,
                'dentist_id' => $this->dentist_id,
                'appointment_type_id' => $this->appointment_type_id,
                'createdAt' => $this->created_at,
                'updatedAt' => $this->updated_at,
            ],
            'relationships' => [
                'patient' => new PatientResource($this->whenLoaded('patient')),
                'dentist' => new DentistResource($this->whenLoaded('dentist')),
                'appointmentType' => new AppointmentTypeResource($this->whenLoaded('appointment_type')),
            ],
            'included' => [
                'patientFullName' => $this->whenLoaded('patient', fn() => $this->patient->fullName),
                'dentistFullName' => $this->whenLoaded('dentist', fn() => $this->dentist->fullName),
                'appointmentTypeName' => $this->whenLoaded('appointment_type', fn() => $this->appointment_type->name),
            ],
            'links' => [
                [
                    'self' => route('v1.appointment.show', [$this->id])
                ]
            ]
        ];
    }
}
