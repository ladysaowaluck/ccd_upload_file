<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\Auth\SanctumAuthService;
use Illuminate\Http\JsonResponse;

// PasswordResetApiController handles password reset requests and password updates
class PasswordResetApiController extends Controller
{
    public function __construct(
        protected SanctumAuthService $authService
    ) {
    }

    // Send a password reset link to the given user.
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $this->authService->sendResetLink($request->input('email'));

        // Always return generic response to prevent user enumeration
        return response()->json([
            'success' => true,
            'message' => 'If the account exists, a password reset link has been sent.',
        ], 200);
    }

    // Reset the user's password and invalidate existing Sanctum tokens.
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $result = $this->authService->resetPassword($request->validated());

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'],
        ], 400);
    }
}

