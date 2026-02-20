<?php

namespace App\Http\Requests;

use App\Models\UserSocials;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Everyone can attempt login
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            // Email/password login
            'email' => 'required_without:provider|email|max:255',
            'password' => 'required_with:email|string|min:6',

            // Provider login
            'provider' => [
                'required_without:email',
                'string',
                Rule::in(array_keys(UserSocials::fGetProviders()))
            ],
            'provider_id' => 'required_with:provider|string|max:255',
            'provider_token' => 'required_with:provider|string',
        ];
    }

    /**
     * Get custom messages for validation errors.
     */
    public function messages(): array
    {
        // Get available providers for the error message
        $availableProviders = implode(', ', array_keys(UserSocials::fGetProviders()));

        return [
            'provider.in' => __('validation.custom.provider.in', ['values' => $availableProviders]),
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'email' => __('validation.attributes.email'),
            'password' => __('validation.attributes.password'),
            'provider' => __('validation.attributes.provider'),
            'provider_id' => __('validation.attributes.provider_id'),
            'provider_token' => __('validation.attributes.provider_token'),
        ];
    }

    /**
     * Check if this is an email login attempt
     */
    public function isEmailLogin(): bool
    {
        return $this->has('email');
    }

    /**
     * Check if this is a social provider login attempt
     */
    public function isProviderLogin(): bool
    {
        return $this->has('provider');
    }

    /**
     * Get the login type for logging/analytics
     */
    public function getLoginType(): string
    {
        if ($this->isEmailLogin()) {
            return 'email';
        }

        if ($this->isProviderLogin()) {
            return $this->input('provider');
        }

        return 'unknown';
    }
}
