<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationPromptController extends Controller
{
    // Display the email verification prompt.
    public function __invoke(Request $request): View
    {
        return view('auth.verify-email');
    }

    // Display the resend verification email prompt.
    public function resendVerify(): View
    {
        return view('auth.resend-email');
    }
}

