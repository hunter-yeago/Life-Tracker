<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWorkoutRequest extends FormRequest
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
            'workout_type_id' => ['required', 'exists:workout_types,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'performed_at' => ['required', 'date'],
            'sets' => ['required', 'array', 'min:1'],
            'sets.*.set_number' => ['required', 'integer', 'min:1'],
            'sets.*.reps' => ['nullable', 'integer', 'min:1'],
            'sets.*.weight' => ['nullable', 'numeric', 'min:0'],
            'sets.*.duration_seconds' => ['nullable', 'integer', 'min:1'],
            'sets.*.difficulty' => ['nullable', 'in:easy,hard,really_hard,almost_fail,fail'],
            'sets.*.completed' => ['boolean'],
            'sets.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'workout_type_id.required' => 'Please select a workout type.',
            'workout_type_id.exists' => 'The selected workout type is invalid.',
            'performed_at.required' => 'Please specify when this workout was performed.',
        ];
    }
}
