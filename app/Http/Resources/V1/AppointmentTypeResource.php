<?php

namespace App\Http\Resources\V1;

use App\Models\AppointmentType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AppointmentType */
class AppointmentTypeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'appointment_type',
            'id' => $this->id,
            'attributes' => [
                'name' => $this->name,
                'description' => $this->when($request->routeIs('v1.appointment-type.show'), $this->description),
                'duration_minutes' => $this->duration_minutes,
                'createdAt' => $this->created_at,
                'updatedAt' => $this->updated_at,
            ],
            'links' => [
                [
                    'self' => route('v1.appointment-type.show', [$this->id])
                ]
            ]
        ];
    }
}
