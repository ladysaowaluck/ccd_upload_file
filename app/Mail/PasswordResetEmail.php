<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PasswordResetEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Authenticatable $user,
        public string $token
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Password Reset Request - ' . config('app.name', 'CCD System'),
        );
    }

    public function content(): Content
    {
        // $frontendUrl = config('app.frontend_url', config('app.url'));
        // $resetUrl = rtrim($frontendUrl, '/') . '/reset-password?token=' . $this->token . '&email=' . urlencode($this->user->email);

        $frontendUrl = env('FRONTEND_URL');
        if (!empty($frontendUrl)) {
            $resetUrl = rtrim($frontendUrl, '/') . '/reset-password?token=' . $this->token . '&email=' . urlencode($this->user->email);
        } else {
            $resetUrl = route('password.reset', [
                'token' => $this->token,
                'email' => $this->user->email,
            ]);
        }

        return new Content(
            htmlString: "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;'>
                    <h2 style='color: #1e293b;'>Password Reset Request</h2>
                    <p style='color: #475569;'>Hello <strong>" . e($this->user->name ?? $this->user->username) . "</strong>,</p>
                    <p style='color: #475569;'>You are receiving this email because we received a password reset request for your account.</p>
                    <p style='margin: 25px 0;'>
                        <a href='{$resetUrl}' style='background-color: #0284c7; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Reset Password</a>
                    </p>
                    <p style='color: #64748b; font-size: 13px;'>Or copy and paste this link in your browser:<br><a href='{$resetUrl}' style='color: #0284c7;'>{$resetUrl}</a></p>
                    <p style='color: #94a3b8; font-size: 12px; margin-top: 30px;'>This password reset link will expire in 60 minutes. If you did not request a password reset, no further action is required.</p>
                </div>
            "
        );
    }
}

