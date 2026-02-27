<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{{ $subject ?? config('app.name') }}</title>
    <style>
        @php
            $branding = App\Services\EmailBrandingService::getBrandingConfig();
        @endphp

        :root {
            --primary-color: {{ $branding['primary_color'] }};
            --secondary-color: {{ $branding['secondary_color'] }};
            --success-color: {{ $branding['success_color'] }};
            --error-color: {{ $branding['error_color'] }};
            --text-color: {{ $branding['text_color'] }};
            --muted-text-color: {{ $branding['muted_text_color'] }};
            --email-width: {{ $branding['email_width'] }};
            --border-radius: {{ $branding['border_radius'] }};
            --font-family: {{ $branding['font_family'] }};
        }

        body {
            font-family: var(--font-family);
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            margin: 0;
            padding: 20px;
            color: var(--text-color);
            line-height: 1.6;
        }

        .email-container {
            max-width: var(--email-width);
            margin: 0 auto;
            background: #ffffff;
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }

        .email-header {
            background: var(--primary-color);
            padding: 30px;
            text-align: center;
            color: white;
        }

        .logo {
            max-height: 50px;
            margin-bottom: 10px;
        }

        .app-name {
            font-size: 24px;
            font-weight: 700;
            margin: 0;
        }

        .email-body {
            padding: 40px;
        }

        .email-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--text-color);
            margin: 0 0 20px 0;
            text-align: center;
        }

        .email-content {
            font-size: 16px;
            color: var(--text-color);
            margin-bottom: 30px;
            text-align: center;
        }

        .email-content p {
            margin: 0 0 15px 0;
        }

        .button-container {
            text-align: center;
            margin: 30px 0;
        }

        .email-button {
            {!! App\Services\EmailBrandingService::getButtonStyle('primary') !!}
        }

        .email-button:hover {
            opacity: 0.8;
        }

        .email-footer {
            background: #f8f9fa;
            padding: 30px;
            text-align: center;
            color: var(--muted-text-color);
            font-size: 14px;
            border-top: 1px solid #e5e7eb;
        }

        .footer-links {
            margin: 15px 0;
        }

        .footer-links a {
            color: var(--primary-color);
            text-decoration: none;
            margin: 0 10px;
        }

        .social-links {
            margin: 20px 0;
        }

        .social-links a {
            display: inline-block;
            margin: 0 8px;
            color: var(--muted-text-color);
            text-decoration: none;
        }

        .subcopy {
            margin-top: 30px;
            padding: 20px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            font-size: 14px;
            color: var(--muted-text-color);
        }

        .subcopy p {
            margin: 0;
        }

        @media only screen and (max-width: 640px) {
            .email-container {
                margin: 0;
                border-radius: 0;
            }

            .email-body, .email-header, .email-footer {
                padding: 20px;
            }

            .email-title {
                font-size: 20px;
            }

            .email-button {
                display: block;
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>
    @php
        $branding = App\Services\EmailBrandingService::getBrandingConfig();
    @endphp

    <div class="email-container">
        {{-- Header --}}
        <div class="email-header">
            @if($branding['logo_url'])
                <img src="{{ $branding['logo_url'] }}" alt="{{ $branding['app_name'] }}" class="logo">
            @endif
            <h1 class="app-name">{{ $branding['app_name'] }}</h1>
        </div>

        {{-- Body --}}
        <div class="email-body">
            @if(isset($title))
                <h1 class="email-title">{{ $title }}</h1>
            @endif

            <div class="email-content">
                {{ $slot }}
            </div>

            @if(isset($actionUrl) && isset($actionText))
                <div class="button-container">
                    <a href="{{ $actionUrl }}" class="email-button" target="_blank">
                        {{ $actionText }}
                    </a>
                </div>
            @endif

            @if(isset($subcopy))
                <div class="subcopy">
                    <p>{{ $subcopy }}</p>
                </div>
            @endif
        </div>

        {{-- Footer --}}
        <div class="email-footer">
            <p>&copy; {{ date('Y') }} {{ $branding['app_name'] }}. @lang('All rights reserved.')</p>

            @if($branding['company_address'])
                <p>{{ $branding['company_address'] }}</p>
            @endif

            @if(array_filter($branding['social_links']))
                <div class="social-links">
                    @foreach($branding['social_links'] as $platform => $url)
                        @if($url)
                            <a href="{{ $url }}" target="_blank">{{ ucfirst($platform) }}</a>
                        @endif
                    @endforeach
                </div>
            @endif

            <div class="footer-links">
                <a href="{{ $branding['app_url'] }}" target="_blank">@lang('Visit Website')</a>
                <a href="{{ $branding['app_url'] }}/privacy" target="_blank">@lang('Privacy Policy')</a>
                <a href="{{ $branding['app_url'] }}/terms" target="_blank">@lang('Terms of Service')</a>
            </div>
        </div>
    </div>
</body>
</html>
