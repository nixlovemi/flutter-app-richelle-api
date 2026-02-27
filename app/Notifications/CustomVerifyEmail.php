<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\App;

class CustomVerifyEmail extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The locale to use for this notification
     */
    public $locale;

    /**
     * Create a notification instance.
     */
    public function __construct($locale = null)
    {
        $this->locale = $locale ?: App::getLocale();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        // Set the locale for this notification
        $localeToUse = $this->locale ?: 'pt_BR';
        App::setLocale($localeToUse);

        $verificationUrl = $this->verificationUrl($notifiable);

        $mailMessage = (new MailMessage)
            ->subject(__('notifications.verify_email_subject'))
            ->markdown('emails.verify-email', [
                'actionUrl' => $verificationUrl,
                'actionText' => __('notifications.verify_email_action'),
                'user' => $notifiable,
            ]);

        return $mailMessage;
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array<int, string>
     */
    public function tags(): array
    {
        return ['verify-email', 'locale:' . $this->locale];
    }

    /**
     * Prepare the notification for queuing.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'locale' => $this->locale,
        ];
    }

    /**
     * Get the verification URL for the given notifiable.
     */
    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
