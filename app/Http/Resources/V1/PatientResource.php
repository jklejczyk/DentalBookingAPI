<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'patient',
            'id' => $this->id,
            'attributes' => [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'pesel' => $this->pesel,
                'email' => $this->email,
                'gender' => $this->gender,
                'birthday' => $this->birthday,
                'address' => $this->address,
                'createdAt' => $this->created_at,
                'updatedAt' => $this->updated_at,
            ],
            'relationships' => [
                'appointments' => AppointmentResource::collection($this->whenLoaded('appointments')),
            ],
            'included' => [
                'genderDescription' => $this->gender->description(),
            ],
            'links' => [
                [
                    'self' => route('v1.patient.show', [$this->id])
                ]
            ]
        ];
    }
}
