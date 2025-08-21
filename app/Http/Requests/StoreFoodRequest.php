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
            'quantity_grams' => ['required', 'numeric', 'min:0.01'],
            'consumed_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'food_type_id.required' => 'Please select a food type.',
            'food_type_id.exists' => 'The selected food type is invalid.',
            'quantity_grams.required' => 'Please specify the quantity consumed.',
            'quantity_grams.min' => 'Quantity must be greater than 0.',
            'consumed_at.required' => 'Please specify when this food was consumed.',
        ];
    }
}
