<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Email Branding Configuration
    |--------------------------------------------------------------------------
    |
    | These settings control the visual branding of all emails sent by your
    | application. Change these values to update colors, fonts, and other
    | design elements across all email templates.
    |
    */

    // Colors - Matching Flutter App Theme
    'primary_color' => env('MAIL_PRIMARY_COLOR', '#231201'),      // Main brand color
    'secondary_color' => env('MAIL_SECONDARY_COLOR', '#C4B59A'),   // Secondary accent
    'success_color' => env('MAIL_SUCCESS_COLOR', '#8B7B6B'),       // Tertiary color
    'error_color' => env('MAIL_ERROR_COLOR', '#1A0D01'),          // Primary dark
    'text_color' => env('MAIL_TEXT_COLOR', '#212121'),            // Black
    'muted_text_color' => env('MAIL_MUTED_TEXT_COLOR', '#424242'), // Dark grey

    // Layout
    'email_width' => env('MAIL_EMAIL_WIDTH', '600px'),
    'border_radius' => env('MAIL_BORDER_RADIUS', '12px'),
    'font_family' => env('MAIL_FONT_FAMILY', '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'),

    // Company Information
    'company_address' => env('MAIL_COMPANY_ADDRESS', null),

    // Social Media Links
    'instagram' => env('MAIL_INSTAGRAM_URL', null),
    'facebook' => env('MAIL_FACEBOOK_URL', null),
    'twitter' => env('MAIL_TWITTER_URL', null),
    'linkedin' => env('MAIL_LINKEDIN_URL', null),
];
