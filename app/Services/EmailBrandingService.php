<?php

namespace App\Services;

class EmailBrandingService
{
    /**
     * Get email branding configuration
     */
    public static function getBrandingConfig(): array
    {
        return [
            // App Identity
            'app_name' => config('app.name', 'TimelessApi'),
            'app_url' => config('app.url', 'https://timeless.app'),
            'logo_url' => config('app.logo_url', null), // Add to .env: APP_LOGO_URL
            
            // Colors (easily changeable)
            'primary_color' => config('mail-branding.primary_color', '#667eea'),
            'secondary_color' => config('mail-branding.secondary_color', '#764ba2'),
            'success_color' => config('mail-branding.success_color', '#10B981'),
            'error_color' => config('mail-branding.error_color', '#EF4444'),
            'text_color' => config('mail-branding.text_color', '#1F2937'),
            'muted_text_color' => config('mail-branding.muted_text_color', '#6B7280'),
            
            // Layout
            'email_width' => config('mail-branding.email_width', '600px'),
            'border_radius' => config('mail-branding.border_radius', '12px'),
            'font_family' => config('mail-branding.font_family', '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif'),
            
            // Footer
            'company_address' => config('mail-branding.company_address', null),
            'social_links' => [
                'instagram' => config('mail-branding.instagram'),
                'facebook' => config('mail-branding.facebook'),
                'twitter' => config('mail-branding.twitter'),
                'linkedin' => config('mail-branding.linkedin'),
            ],
        ];
    }

    /**
     * Get CSS variables for email styling
     */
    public static function getCssVariables(): string
    {
        $config = self::getBrandingConfig();
        
        return "
            :root {
                --primary-color: {$config['primary_color']};
                --secondary-color: {$config['secondary_color']};
                --success-color: {$config['success_color']};
                --error-color: {$config['error_color']};
                --text-color: {$config['text_color']};
                --muted-text-color: {$config['muted_text_color']};
                --email-width: {$config['email_width']};
                --border-radius: {$config['border_radius']};
                --font-family: {$config['font_family']};
            }
        ";
    }

    /**
     * Get button style for specific type
     */
    public static function getButtonStyle(string $type = 'primary'): string
    {
        $config = self::getBrandingConfig();
        
        $colors = [
            'primary' => $config['primary_color'],
            'success' => $config['success_color'],
            'error' => $config['error_color'],
        ];
        
        $backgroundColor = $colors[$type] ?? $colors['primary'];
        
        return "
            display: inline-block;
            padding: 14px 28px;
            background-color: {$backgroundColor};
            color: #ffffff;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 16px;
            text-align: center;
            margin: 20px 0;
        ";
    }
}