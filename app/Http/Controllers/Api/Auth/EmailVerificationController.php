<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResendVerificationRequest;
use App\Services\Auth\SanctumAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// EmailVerificationController handles signature-verified email activation and link resending
class EmailVerificationController extends Controller
{
    public function __construct(
        protected SanctumAuthService $authService
    ) {
    }

    // Mark the user's email address as verified using a signed URL.

    public function verify(Request $request, int|string $id, string $hash): JsonResponse
    {
        // 1. Validate the URL signature
        if (!$request->hasValidSignature()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification link.',
            ], 403);
        }

        // 2. Execute verification via service
        $result = $this->authService->verifyEmail($id, $hash);

        return match ($result['status']) {
            'verified' => response()->json([
                'success' => true,
                'message' => $result['message'],
            ], 200),
            'already_verified' => response()->json([
                'success' => true,
                'message' => $result['message'],
            ], 200),
            'not_found' => response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 404),
            default => response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 403),
        };
    }

    // Resend the email verification notification.
    public function resend(ResendVerificationRequest $request): JsonResponse
    {
        $result = $this->authService->resendVerificationEmail($request->input('email'));

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ], 200);
    }
}

