<?php

namespace App\Http\Requests\Api\V1\Patient;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BasePatientRequest extends FormRequest
{
    public function mappedAttributes(): array
    {
        $attributeMap = [
            'data.attributes.first_name' => 'first_name',
            'data.attributes.last_name' => 'last_name',
            'data.attributes.gender' => 'gender',
            'data.attributes.birthday' => 'birthday',
            'data.attributes.pesel' => 'pesel',
            'data.attributes.email' => 'email',
            'data.attributes.address' => 'address',
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
