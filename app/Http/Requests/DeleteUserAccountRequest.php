<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

class DeleteUserAccountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        // Get user to check if they have a password
        $user = $this->getAuthenticatedUser();
        $hasPassword = $user && !is_null($user->password);

        return [
            'password' => $hasPassword ? ['required', 'string', 'min:8'] : ['nullable'],
            'confirmation' => ['required', 'boolean', 'accepted'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'password.required' => __('messages.validation.required', ['attribute' => __('messages.password')]),
            'password.min' => __('messages.validation.min.string', ['attribute' => __('messages.password'), 'min' => 8]),
            'confirmation.required' => __('messages.validation.required', ['attribute' => __('messages.confirmation')]),
            'confirmation.accepted' => __('messages.account_deletion_must_be_confirmed'),
        ];
    }

    /**
     * Get the authenticated user from the bearer token.
     *
     * @return User|null
     */
    private function getAuthenticatedUser(): ?User
    {
        $user = $this->user();

        return $user instanceof User ? $user : null;
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validatePassword($validator);
        });
    }

    /**
     * Validate that the provided password matches the user's current password.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    private function validatePassword(Validator $validator): void
    {
        $user = $this->getAuthenticatedUser();

        if (!$user) {
            return;
        }

        // If user has a password, validate it
        if (!is_null($user->password)) {
            $providedPassword = $this->input('password');

            // Password is required for users who have one
            if (empty($providedPassword)) {
                $validator->errors()->add('password', __('messages.validation.required', ['attribute' => __('messages.password')]));
                return;
            }

            // Validate the password matches
            if (!Hash::check($providedPassword, $user->password)) {
                $validator->errors()->add('password', __('messages.validation.password_incorrect'));
            }
        }
        // For social login users without password, no password validation needed
    }
}
