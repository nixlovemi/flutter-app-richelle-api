# Email Template System

## Overview 📧

This Laravel application now has a **reusable email template system** that makes it easy to:

- ✅ Maintain consistent branding across all emails
- ✅ Easily change colors, fonts, and design elements 
- ✅ Create new emails quickly using the base template
- ✅ Support multiple languages (Portuguese/English)

## Current Implementation ⚡

### 1. Email Branding Service
**Location:** `app/Services/EmailBrandingService.php`

Central service that manages all email branding configuration:

```php
// Get branding colors, fonts, layout settings
$branding = EmailBrandingService::getBrandingConfig();

// Get CSS variables for consistent styling 
$css = EmailBrandingService::getCssVariables();

// Get button styles for different types (primary, success, error)
$primaryButtonStyle = EmailBrandingService::getButtonStyle('primary');
```

### 2. Configuration File
**Location:** `config/mail-branding.php`

Centralized configuration for all email design elements:

```php
// Colors
'primary_color' => '#667eea',
'secondary_color' => '#764ba2', 
'success_color' => '#10B981',
'error_color' => '#EF4444',

// Layout
'email_width' => '600px',
'font_family' => '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',

// Company info
'company_address' => 'Your Company Address',
'instagram' => 'https://instagram.com/yourcompany',
```

### 3. Base Email Template
**Location:** `resources/views/emails/layouts/base.blade.php`

Reusable layout with:
- Branded header with logo and app name
- Consistent styling using CSS variables
- Action button support
- Professional footer with social links
- Mobile-responsive design
- Automatic locale support

### 4. Custom Blade Component
**Registered in:** `app/Providers/AppServiceProvider.php`

```php
// Register the mail layout component
Blade::component('emails.layouts.base', 'mail-layout');
```

## How to Change Branding 🎨

### Environment Variables (.env)
```bash
# Add these to your .env file for easy customization
MAIL_PRIMARY_COLOR=#667eea
MAIL_SECONDARY_COLOR=#764ba2
MAIL_SUCCESS_COLOR=#10B981
MAIL_ERROR_COLOR=#EF4444
MAIL_COMPANY_ADDRESS="123 Main St, City, Country"
MAIL_INSTAGRAM_URL=https://instagram.com/yourcompany
MAIL_FACEBOOK_URL=https://facebook.com/yourcompany
APP_LOGO_URL=https://yoursite.com/logo.png
```

### Quick Design Changes

**Change Primary Color:**
```bash
MAIL_PRIMARY_COLOR=#ff6b6b  # New red theme
```

**Add Company Logo:**
```bash  
APP_LOGO_URL=https://yoursite.com/new-logo.png
```

**Update Social Links:**
```bash
MAIL_INSTAGRAM_URL=https://instagram.com/newhandle
MAIL_LINKEDIN_URL=https://linkedin.com/company/yourcompany
```

## Creating New Emails 📝

### Method 1: Using Laravel Mail Components (Current)

**Example: Welcome Email**
```php
// resources/views/emails/welcome.blade.php
@component('mail::message')
# Welcome to {{ config('app.name') }}!

Hello {{ $user->first_name }},

Welcome to our platform! We're excited to have you on board.

@component('mail::button', ['url' => config('app.url') . '/dashboard'])
Get Started
@endcomponent

If you have any questions, feel free to contact us.

Thanks,<br>
{{ config('app.name') }} Team
@endcomponent
```

### Method 2: Using Custom Layout (Future Implementation)

**Example: Password Reset Email**
```php
// resources/views/emails/password-reset.blade.php
<x-mail-layout 
    title="Reset Your Password"
    :actionUrl="$resetUrl"
    actionText="Reset Password"
>
    <p>Hello {{ $user->first_name }},</p>
    <p>You requested a password reset for your account.</p>
    <p>This link will expire in 60 minutes.</p>
</x-mail-layout>
```

## Example Notifications 🔔

### Email Verification (Current)
**Files:**
- `app/Notifications/CustomVerifyEmail.php` 
- `resources/views/emails/verify-email.blade.php`

### Future Email Examples Created

1. **Welcome Email** (`resources/views/emails/welcome.blade.php`)
   - Send after successful email verification
   - Branded welcome message with call-to-action

2. **Password Reset** (`resources/views/emails/password-reset.blade.php`) 
   - Secure password reset with branded styling
   - Automatic expiration notice

3. **General Notification** (`resources/views/emails/notification.blade.php`)
   - Flexible template for any notification type
   - Supports dynamic content and actions

## Benefits of This System ✨

### For Developers:
- **Consistency** - All emails look professional and branded
- **Maintainability** - Change colors/fonts from one config file
- **Speed** - Quick template creation for new email types
- **Localization** - Automatic Portuguese/English support

### For Business:
- **Brand Recognition** - Consistent visual identity
- **Easy Updates** - Change logo/colors without developer time
- **Professional Look** - Beautiful, mobile-responsive emails
- **Scalability** - Easy to add new email types

## Migration Strategy 📈

### Phase 1: ✅ Foundation (Completed)
- Email Branding Service created
- Configuration system established  
- Base template designed
- Current verification email working

### Phase 2: 🚀 Future Implementation  
- Migrate existing emails to use base template
- Implement custom Blade components properly
- Add more email types (welcome, password reset, etc.)
- Add advanced features (email analytics, A/B testing)

### Phase 3: 🎯 Advanced Features
- Email template builder UI
- Dynamic content blocks
- Advanced personalization
- Email performance metrics

## Quick Start Guide 🚀

### 1. Change Your Brand Colors
```bash
# Add to .env
MAIL_PRIMARY_COLOR=#your-brand-color
MAIL_SECONDARY_COLOR=#your-secondary-color
```

### 2. Add Your Logo
```bash  
# Add to .env
APP_LOGO_URL=https://yoursite.com/logo.png
```

### 3. Create a New Email
```php
// Create notification class
php artisan make:notification WelcomeEmail

// Use the email template system
@component('mail::message')
# Your Email Title
Your content here...
@endcomponent
```

### 4. Test Your Changes
```bash
php artisan config:clear
php artisan view:clear
```

---

**Ready to create beautiful, consistent emails! 🎉**

Need to add a new email type? Just follow the examples above and your emails will automatically match your brand.
