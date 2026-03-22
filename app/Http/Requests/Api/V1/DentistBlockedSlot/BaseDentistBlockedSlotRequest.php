<?php

namespace App\Http\Requests\Api\V1\DentistBlockedSlot;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BaseDentistBlockedSlotRequest extends FormRequest
{
    public function mappedAttributes(): array
    {
        $attributeMap = [
            'data.attributes.reason' => 'reason',
            'data.attributes.start' => 'start',
            'data.attributes.end' => 'end',
        ];

        $attributesToUpdate = [];
        foreach ($attributeMap as $key => $attribute) {
            if ($this->has($key)) {
                $attributesToUpdate[$attribute] = $this->input($key);
            }
        }

        return $attributesToUpdate;
    }
}
