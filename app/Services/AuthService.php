<?php

namespace App\Services;

use App\Enums\WebLoginResult;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthService
{
    public function __construct(
        protected UserService $userService
    ) {}

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

    /**
     * Queue a password reset link for the email. Returns a Password broker status string.
     */
    public function sendPasswordResetLink(string $email): string
    {
        return Password::sendResetLink(['email' => $email]);
    }

    /**
     * Complete password reset using the emailed token.
     *
     * @param  array{email: string, password: string, password_confirmation: string, token: string}  $credentials
     */
    public function resetPasswordWithToken(array $credentials): string
    {
        return Password::reset(
            $credentials,
            function (User $user) use ($credentials) {
                $user->forceFill([
                    'password' => Hash::make($credentials['password']),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));

                $this->userService->notifyPasswordChanged($user->fresh());
            }
        );
    }
}
