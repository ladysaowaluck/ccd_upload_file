<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RequestLoginAuth extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $mailSubject,
        public string $url
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->mailSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: "
                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;'>
                    <h2 style='color: #1e293b;'>CCD System Login Privileges Request</h2>
                    <p style='color: #475569;'>You requested login privileges for the Customs Clearance Database System.</p>
                    <p style='color: #475569;'>Please click the button below to verify and grant system login access to your account:</p>
                    <p style='margin: 25px 0;'>
                        <a href='{$this->url}' style='background-color: #0284c7; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Grant System Login Privileges</a>
                    </p>
                    <p style='color: #64748b; font-size: 13px;'>Or copy and paste this link in your browser:<br><a href='{$this->url}' style='color: #0284c7;'>{$this->url}</a></p>
                    <p style='color: #94a3b8; font-size: 12px; margin-top: 30px;'>If you did not request system authorization, please ignore this email.</p>
                </div>
            "
        );
    }
}

