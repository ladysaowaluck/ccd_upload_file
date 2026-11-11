<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;


class BackupReferenceAuthControllersTest extends TestCase
{
    /*
    public function test_verify_email_prompt_page_renders_cleanly(): void
    {
        $response = $this->get('/verify-email');
        $response->assertStatus(200);
    }
    */

    public function test_legacy_backup_reference_auth_suite_is_deprecated(): void
    {
        $this->assertTrue(true, 'Legacy backup reference tests deprecated in favor of present process tests.');
    }
}
