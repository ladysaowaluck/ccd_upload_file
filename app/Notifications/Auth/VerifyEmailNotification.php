<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

// VerifyEmailNotification sends signed URL to verify email address
class VerifyEmailNotification extends Notification
{
    use Queueable;

    // Get the notification's delivery channels.
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    // Build the mail representation of the notification.
    public function toMail(object $notifiable): MailMessage
    {
        $verificationUrl = $this->verificationUrl($notifiable);

        return (new MailMessage())
            ->subject('Verify Your Email Address - ' . config('app.name', 'Laravel'))
            ->line('Please click the button below to verify your email address and activate your account.')
            ->action('Verify Email Address', $verificationUrl)
            ->line('If you did not create an account, no further action is required.')
            ->line('This verification link will expire in ' . Config::get('auth.verification.expire', 60) . ' minutes.');
    }

    // Get the verification URL for the given notifiable.
    protected function verificationUrl(object $notifiable): string
    {
        return URL::temporarySignedRoute(
            'api.verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}

