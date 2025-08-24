<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFoodRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'food_type_id' => ['required', 'exists:food_types,id'],
            'servings' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'food_type_id.required' => 'Please select a food type.',
            'food_type_id.exists' => 'The selected food type is invalid.',
            'servings.required' => 'Please specify the number of servings.',
            'servings.min' => 'Servings must be greater than 0.',
        ];
    }
}
