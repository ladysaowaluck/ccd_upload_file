<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\RequestLoginAuth;
use App\Models\Auth\AdminUser;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class EmailRequestSystemAuth extends Controller
{
    // Display the system auth request view.
    public function SystemAuth(): View
    {
        return view('auth.request-login-auth');
    }

    // Process system authorization request and send email.
    public function SystemAuthReq(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $email = strtolower(trim($request->email));
        $user = AdminUser::where('email', $email)->first();

        if ($user === null) {
            return back()->withInput()->withErrors([
                'error' => "Don't have e-mail address in user list",
            ]);
        }

        $userRoleCount = DB::connection('authdb')
            ->table('ccd_admin_role_users')
            ->where('user_id', $user->id)
            ->count();

        if ($userRoleCount === 0) {
            $userEmail = $user->email;
            $subject = '[CCD System] System application verify';
            $emailToken = mt_rand(100000000, 999999999) . uniqid();
            $emailTokenAt = Carbon::now()->format('YmdHis');

            $user->update([
                'email_token' => $emailToken,
                'email_token_at' => $emailTokenAt,
            ]);

            $url = url('req_system_auth_verify/' . $emailTokenAt . '/' . $emailToken);

            try {
                Mail::to($userEmail)->send(new RequestLoginAuth($subject, $url));
                $redirect = back()->with('status', 'E-mail is sent. Please check and confirm your e-mail');

                // If in dev/log mailer mode, provide clickable activation link on screen
                if (config('mail.default') === 'log' || app()->environment('local', 'testing')) {
                    $redirect->with('verify_url', $url);
                }

                return $redirect;
            } catch (\Throwable $err) {
                Log::error("Could not send system auth email to {$userEmail}: " . $err->getMessage());
                return back()->withErrors(['error' => 'System problem, please contact IT.']);
            }
        }

        return back()->withInput()->withErrors([
            'error' => 'Your role is exist. please try login',
        ]);
    }

    // Verify token from email and grant role_id = 3 (staff).
    public function SystemAuthVerify(Request $request, $time = null, $token = null): RedirectResponse
    {
        $time = $time ?? $request->route('time') ?? $request->input('time');
        $token = $token ?? $request->route('token') ?? $request->input('token');

        if (!$time || !$token) {
            return redirect()->route('login')->withErrors(['verification' => 'Wrong URL']);
        }

        $user = AdminUser::where([
            ['email_token', '=', $token],
            ['email_token_at', '=', $time],
        ])->first();

        if ($user) {
            DB::connection('authdb')->table('ccd_admin_role_users')
                ->insertOrIgnore([
                    'role_id' => 3,
                    'user_id' => $user->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            // Clear token
            $user->update([
                'email_token' => null,
                'email_token_at' => null,
            ]);

            return redirect()->route('login')->with('verified', 'Register complete!! Please try to login');
        }

        return redirect()->route('login')->withErrors([
            'verification' => 'No user information or invalid link',
        ]);
    }
}

