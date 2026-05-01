<?php

return [
    'welcome' => 'Welcome to our application!',
    'hello' => 'Hello :name!',
    'goodbye' => 'Goodbye!',
    'success' => 'Operation completed successfully!',
    'error' => 'An error occurred. Please try again.',
    'not_found' => 'Resource not found.',
    'unauthorized' => 'You are not authorized to access this resource.',
    'forbidden' => 'Access denied.',
    'validation_error' => 'Please check your input and try again.',
    'save' => 'Save',
    'cancel' => 'Cancel',
    'edit' => 'Edit',
    'delete' => 'Delete',
    'confirm_delete' => 'Are you sure you want to delete this item?',
    'created_at' => 'Created at',
    'updated_at' => 'Updated at',
    'updated_success' => ':attribute updated successfully!',
    'update_failed' => 'Failed to update :attribute. Please try again.',

    // Authentication and authorization
    'token_required' => 'Authentication token is required.',
    'invalid_token' => 'Invalid or expired authentication token.',
    'user_not_found' => 'User not found.',

    // Account deletion
    'account_deleted_successfully' => 'Your account has been permanently deleted.',
    'account_deletion_failed' => 'Failed to delete your account. Please try again or contact support.',
    'account_deletion_must_be_confirmed' => 'You must confirm that you want to permanently delete your account.',

    // Field names for validation
    'password' => 'password',
    'confirmation' => 'confirmation',

    'social' => [
        'facebook' => 'Facebook',
        'google' => 'Google',
    ],

    'models' => [
        'user' => [
            'name' => 'User',
        ],
    ],

    'validation' => [
        'required' => 'The :attribute field is required.',
        'min' => [
            'string' => 'The :attribute must be at least :min characters.',
        ],
        'password_incorrect' => 'The provided password is incorrect.',
    ],
];
