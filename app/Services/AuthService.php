<?php

namespace App\Services;

use App\Enums\WebLoginResult;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthService
{
    /**
     * Attempt session (web) login: credentials, active check, session regeneration on success.
     */
    public function attemptWebLogin(Request $request, string $email, string $password, bool $remember): WebLoginResult
    {
        if (! Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            return WebLoginResult::InvalidCredentials;
        }

        $user = Auth::user();

        if ($user !== null && ! $user->isActive()) {
            $this->logoutWeb($request);

            return WebLoginResult::AccountInactive;
        }

        $request->session()->regenerate();

        return WebLoginResult::Success;
    }

    /**
     * End the web session: logout, invalidate session, rotate CSRF token.
     */
    public function logoutWeb(Request $request): void
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }
}
