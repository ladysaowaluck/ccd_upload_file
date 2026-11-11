<?php

namespace App\Services\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;

class JwtService
{
    /**
     * Supported signing algorithm.
     */
    protected const ALGORITHM = 'HS256';

    /**
     * Get the HMAC secret key.
     *
     * @return string
     */
    protected function getSecret(): string
    {
        $secret = config('jwt.secret') ?: env('JWT_SECRET');

        if (!$secret) {
            $secret = config('app.key');
            if (str_starts_with($secret, 'base64:')) {
                $secret = base64_decode(substr($secret, 7));
            }
        }

        return (string) $secret;
    }

    /**
     * Generate an RFC 7519 JSON Web Token for an authenticatable user.
     *
     * @param Authenticatable $user
     * @param int $ttlMinutes
     * @param array $customClaims
     * @return string
     */
    public function generateToken(Authenticatable $user, int $ttlMinutes = 60, array $customClaims = []): string
    {
        $now = time();
        $expiration = $now + ($ttlMinutes * 60);

        $header = [
            'typ' => 'JWT',
            'alg' => self::ALGORITHM,
        ];

        $payload = array_merge([
            'iss' => config('app.url', 'http://localhost'),
            'sub' => $user->getAuthIdentifier(),
            'prv' => get_class($user),
            'jti' => Str::uuid()->toString(),
            'iat' => $now,
            'nbf' => $now,
            'exp' => $expiration,
            'user' => [
                'id' => $user->getAuthIdentifier(),
                'username' => $user->username ?? null,
                'email' => $user->email ?? null,
            ],
        ], $customClaims);

        $headerEncoded = $this->base64UrlEncode(json_encode($header, JSON_UNESCAPED_SLASHES));
        $payloadEncoded = $this->base64UrlEncode(json_encode($payload, JSON_UNESCAPED_SLASHES));

        $signature = hash_hmac('sha256', "{$headerEncoded}.{$payloadEncoded}", $this->getSecret(), true);
        $signatureEncoded = $this->base64UrlEncode($signature);

        $jwtToken = "{$headerEncoded}.{$payloadEncoded}.{$signatureEncoded}";

        // Persist token hash in personal_access_tokens for revocation and multi-session management
        if (method_exists($user, 'tokens')) {
            $user->tokens()->create([
                'name' => 'jwt_token',
                'token' => hash('sha256', $jwtToken),
                'abilities' => ['*'],
                'expires_at' => now()->addMinutes($ttlMinutes),
            ]);
        }

        return $jwtToken;
    }

    /**
     * Validate an RFC 7519 JWT token and return its payload if valid, null otherwise.
     *
     * @param string $token
     * @return array|null
     */
    public function validateToken(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        [$headerB64, $payloadB64, $signatureB64] = $parts;

        // 1. Decode header and verify algorithm (Prevent algorithm confusion attacks)
        $headerJson = $this->base64UrlDecode($headerB64);
        $header = json_decode($headerJson, true);

        if (!is_array($header) || !isset($header['alg']) || $header['alg'] !== self::ALGORITHM) {
            return null; // Reject 'none', unsupported, or missing algorithms
        }

        // 2. Verify HMAC-SHA256 signature using constant-time comparison
        $expectedSignature = hash_hmac('sha256', "{$headerB64}.{$payloadB64}", $this->getSecret(), true);
        $expectedSignatureB64 = $this->base64UrlEncode($expectedSignature);

        if (!hash_equals($expectedSignatureB64, $signatureB64)) {
            return null; // Signature mismatch or payload tampering
        }

        // 3. Decode payload and verify claims
        $payloadJson = $this->base64UrlDecode($payloadB64);
        $payload = json_decode($payloadJson, true);

        if (!is_array($payload)) {
            return null;
        }

        // Verify expiration (exp)
        if (isset($payload['exp']) && (int) $payload['exp'] < time()) {
            return null; // Token expired
        }

        // Verify not before (nbf)
        if (isset($payload['nbf']) && (int) $payload['nbf'] > time()) {
            return null; // Token not yet valid
        }

        return $payload;
    }

    /**
     * Decode an unverified token payload for inspection.
     *
     * @param string $token
     * @return array|null
     */
    public function parsePayload(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        $payloadJson = $this->base64UrlDecode($parts[1]);
        $payload = json_decode($payloadJson, true);

        return is_array($payload) ? $payload : null;
    }

    /**
     * Decode an unverified token header for inspection.
     *
     * @param string $token
     * @return array|null
     */
    public function parseHeader(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        $headerJson = $this->base64UrlDecode($parts[0]);
        $header = json_decode($headerJson, true);

        return is_array($header) ? $header : null;
    }

    /**
     * URL-safe Base64 encode without padding.
     *
     * @param string $data
     * @return string
     */
    public function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * URL-safe Base64 decode.
     *
     * @param string $data
     * @return string
     */
    public function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return (string) base64_decode(strtr($data, '-_', '+/'));
    }
}

