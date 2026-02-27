<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('auth.email_verification_title') }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        .success-icon {
            font-size: 4rem;
            color: #10B981;
            margin-bottom: 20px;
        }
        .error-icon {
            font-size: 4rem;
            color: #EF4444;
            margin-bottom: 20px;
        }
        h1 {
            color: #1F2937;
            margin-bottom: 10px;
            font-size: 2rem;
            font-weight: 600;
        }
        p {
            color: #6B7280;
            font-size: 1.1rem;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .app-name {
            color: #667eea;
            font-weight: 600;
        }
        .close-instruction {
            font-size: 0.9rem;
            color: #9CA3AF;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        @if($success)
            <div class="success-icon">✅</div>
            <h1>{{ __('auth.email_verification_success') }}</h1>
            <p>{{ __('auth.email_verified_web_message') }}</p>
        @else
            <div class="error-icon">❌</div>
            <h1>{{ __('auth.invalid_verification_link') }}</h1>
            <p>{{ __('auth.invalid_verification_message') }}</p>
        @endif

        <p class="close-instruction">
            {{ __('auth.close_window_instruction') }}
        </p>

        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #E5E7EB;">
            <span class="app-name">{{ config('app.name') }}</span>
        </div>
    </div>
</body>
</html>
