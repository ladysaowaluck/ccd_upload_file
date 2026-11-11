<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VerificationEmail extends Mailable
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
            subject: 'Verify Your Email Address - ' . config('app.name', 'CCD System'),
        );
    }

    public function content(): Content
    {
        $verifyUrl = url('/api/email/verify/' . $this->token);

        return new Content(
            htmlString: "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;'>
                    <h2 style='color: #1e293b;'>Account Verification</h2>
                    <p style='color: #475569;'>Hello <strong>" . e($this->user->name ?? $this->user->username) . "</strong>,</p>
                    <p style='color: #475569;'>Thank you for registering. Please click the link below to verify your email address and activate your account:</p>
                    <p style='margin: 25px 0;'>
                        <a href='{$verifyUrl}' style='background-color: #0284c7; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Verify Email & Activate Account</a>
                    </p>
                    <p style='color: #64748b; font-size: 13px;'>Or copy and paste this link in your browser:<br><a href='{$verifyUrl}' style='color: #0284c7;'>{$verifyUrl}</a></p>
                    <p style='color: #94a3b8; font-size: 12px; margin-top: 30px;'>This link will expire in 24 hours. If you did not create an account, please ignore this email.</p>
                </div>
            "
        );
    }
}

