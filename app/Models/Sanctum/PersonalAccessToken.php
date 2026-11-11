<?php

namespace App\Models\Sanctum;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

// PersonalAccessToken model explicitly bound to mysql connection (ccd_system_rds) so both AdminUser (authdb) and User can store API tokens in ccd_system_rds
class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'mysql';

    // Find token instance with dual support for RFC 7519 JWT tokens and Sanctum pipe tokens
    public static function findToken($token)
    {
        // If the token is a 3-part RFC 7519 JWT, validate cryptographic signature and expiration first
        if (is_string($token) && substr_count($token, '.') === 2) {
            $jwtService = app(\App\Services\Auth\JwtService::class);
            $payload = $jwtService->validateToken($token);

            if (!$payload) {
                return null; // Cryptographic signature failed, tampered, or expired
            }

            // Verify the token is active in the database (not revoked via logout or password reset)
            $hashed = hash('sha256', $token);
            return static::where('token', $hashed)->first();
        }

        return parent::findToken($token);
    }
}

