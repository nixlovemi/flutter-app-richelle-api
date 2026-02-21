<?php

namespace App\Traits;

trait UserProfileValidation
{
    /**
     * Get the validation rules for user profile fields.
     *
     * @return array<string, string>
     */
    public static function getUserProfileValidationRules(): array
    {
        return [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
        ];
    }

    /**
     * Validate user profile data.
     *
     * @param array $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    public function validateUserProfile(array $data): \Illuminate\Contracts\Validation\Validator
    {
        return \Illuminate\Support\Facades\Validator::make($data, static::getUserProfileValidationRules());
    }
}
