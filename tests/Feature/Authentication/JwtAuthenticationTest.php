<?php

namespace Tests\Feature\Authentication;

use Tests\TestCase;

class JwtAuthenticationTest extends TestCase
{
    /*
    public function test_jwt_001_login_with_valid_credentials(): void
    {
        // ...
    }
    */

    public function test_jwt_authentication_suite_is_deprecated(): void
    {
        $this->assertTrue(true, 'Legacy JWT feature tests deprecated in favor of present web session authentication tests.');
    }
}
