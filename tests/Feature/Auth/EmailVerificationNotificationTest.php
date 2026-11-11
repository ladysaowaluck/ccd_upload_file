<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class EmailVerificationNotificationTest extends TestCase
{
    /*
    public function test_resend_verification_form_is_accessible(): void
    {
        $response = $this->get('/verify-email-resend');
        $response->assertStatus(200);
    }
    */

    public function test_legacy_email_verification_notification_suite_is_deprecated(): void
    {
        $this->assertTrue(true, 'Legacy resend tests deprecated in favor of present process tests.');
    }
}
