<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWorkoutTypeRequest extends FormRequest
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
        $workoutType = $this->route('workout_type');

        \Log::info('StoreWorkoutTypeRequest rules', [
            'route_param' => $workoutType,
            'route_param_id' => $workoutType?->id ?? null,
            'route_param_type' => gettype($workoutType),
            'route_param_class' => is_object($workoutType) ? get_class($workoutType) : null,
            'all_route_params' => $this->route()->parameters(),
        ]);

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('workout_types', 'name')->ignore($workoutType?->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'muscle_group' => ['required', 'string', 'max:255'],
            'equipment_needed' => ['nullable', 'string', 'max:255'],
            'sides' => ['required', 'in:both,separate,none'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'equipment_needed' => $this->equipment_needed ?: null,
            'description' => $this->description ?: null,
        ]);
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Please provide a name for this workout type.',
            'name.unique' => 'A workout type with this name already exists.',
            'muscle_group.required' => 'Please specify the muscle group this workout targets.',
            'sides.required' => 'Please specify if this workout supports left/right sides.',
        ];
    }
}
