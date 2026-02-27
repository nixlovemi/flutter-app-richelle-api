@component('mail::message')
{{-- Greeting --}}
# {{ __('notifications.Hello!') }} @if(isset($user) && $user->first_name){{ $user->first_name }}@endif!

{{-- Content --}}
{{ __('notifications.verify_email_line1') }}

@component('mail::button', ['url' => $actionUrl])
{{ $actionText }}
@endcomponent

{{ __('notifications.verify_email_line2') }}

{{-- Salutation --}}
{{ __('notifications.Regards') }},<br>
{{ config('app.name') }}

{{-- Subcopy --}}
@slot('subcopy')
@if(App::getLocale() === 'pt_BR' || App::getLocale() === 'pt')
Se você estiver com problemas para clicar no botão "{{ $actionText }}", copie e cole o URL abaixo no seu navegador: <a href="{{ $actionUrl }}" style="color: #231201; text-decoration: underline;">{{ $actionUrl }}</a>
@else
If you're having trouble clicking the "{{ $actionText }}" button, copy and paste the URL below into your web browser: <a href="{{ $actionUrl }}" style="color: #231201; text-decoration: underline;">{{ $actionUrl }}</a>
@endif
@endslot
@endcomponent
