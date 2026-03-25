<?php

namespace App\Http\Requests\Api\V1\Patient;

use App\Http\Enums\GenderEnum;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StorePatientRequest extends BasePatientRequest
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
            'data.attributes.first_name' => 'required|string',
            'data.attributes.last_name' => 'required|string',
            'data.attributes.gender' => ['nullable', 'sometimes', 'string', Rule::in(array_column(GenderEnum::cases(), 'value'))],
            'data.attributes.birthday' => 'required|date',
            'data.attributes.pesel' => 'required|string|size:11|regex:/^[0-9]{11}$/',
            'data.attributes.address' => 'nullable|sometimes|string',
            'data.attributes.email' => 'nullable|sometimes|string|email',
        ];
    }
}
