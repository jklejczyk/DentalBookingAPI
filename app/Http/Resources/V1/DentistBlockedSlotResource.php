<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DentistBlockedSlotResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'dentist_blocked_slot',
            'id' => $this->id,
            'attributes' => [
                'dentist_id' => $this->dentist_id,
                'start' => $this->start,
                'end' => $this->end,
                'reason' => $this->reason,
                'createdAt' => $this->created_at,
                'updatedAt' => $this->updated_at,
            ],
            'relationships' => [
                'dentist' => new DentistResource($this->whenLoaded('dentist')),
            ],
            'links' => []
        ];
    }
}
