<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization will be handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return \App\Models\User::getUserProfileValidationRules();
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'first_name.required' => __('messages.validation.first_name_required'),
            'first_name.string' => __('messages.validation.first_name_string'),
            'first_name.max' => __('messages.validation.first_name_max'),
            'last_name.required' => __('messages.validation.last_name_required'),
            'last_name.string' => __('messages.validation.last_name_string'),
            'last_name.max' => __('messages.validation.last_name_max'),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'first_name' => __('messages.models.user.first_name'),
            'last_name' => __('messages.models.user.last_name'),
        ];
    }
}
