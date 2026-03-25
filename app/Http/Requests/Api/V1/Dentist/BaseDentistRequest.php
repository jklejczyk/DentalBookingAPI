<?php

namespace App\Http\Requests\Api\V1\Dentist;

use Illuminate\Foundation\Http\FormRequest;

class BaseDentistRequest extends FormRequest
{
    public function mappedAttributes(): array
    {
        $attributeMap = [
            'data.attributes.first_name' => 'first_name',
            'data.attributes.last_name' => 'last_name',
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
