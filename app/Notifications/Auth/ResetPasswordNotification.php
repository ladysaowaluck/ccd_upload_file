<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Config;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    public function __construct(public string $token)
    {
    }

    // Get the notification's delivery channels.
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    // Build the mail representation of the notification.
    public function toMail(object $notifiable): MailMessage
    {
        // $frontendUrl = config('app.frontend_url', config('app.url'));
        // $resetUrl = rtrim($frontendUrl, '/') . '/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->getEmailForPasswordReset());

        $frontendUrl = env('FRONTEND_URL');
        if (!empty($frontendUrl)) {
            $resetUrl = rtrim($frontendUrl, '/') . '/reset-password?token=' . $this->token . '&email=' . urlencode($notifiable->getEmailForPasswordReset());
        } else {
            $resetUrl = route('password.reset', [
                'token' => $this->token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ]);
        }

        return (new MailMessage())
            ->subject('Reset Password Notification - ' . config('app.name', 'Laravel'))
            ->line('You are receiving this email because we received a password reset request for your account.')
            ->action('Reset Password', $resetUrl)
            ->line('This password reset link will expire in ' . Config::get('auth.passwords.users.expire', 60) . ' minutes.')
            ->line('If you did not request a password reset, no further action is required.');
    }
}

