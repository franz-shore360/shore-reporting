<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Enums\WebLoginResult;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Show the login form.
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Handle a login request.
     *
     * @return RedirectResponse|JsonResponse
     */
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        $result = $this->authService->attemptWebLogin(
            $request,
            $validated['email'],
            $validated['password'],
            $request->boolean('remember')
        );

        return match ($result) {
            WebLoginResult::Success => $this->loginSuccessResponse($request),
            WebLoginResult::AccountInactive => $this->inactiveAccountResponse($request),
            WebLoginResult::InvalidCredentials => $this->invalidCredentialsResponse($request),
        };
    }

    /**
     * Log the user out.
     *
     * @return RedirectResponse|JsonResponse
     */
    public function logout(Request $request)
    {
        $this->authService->logoutWeb($request);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Logged out']);
        }

        return redirect()->route('login');
    }

    private function loginSuccessResponse(LoginRequest $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['user' => Auth::user()]);
        }

        return redirect()->intended(route('home'));
    }

    private function inactiveAccountResponse(LoginRequest $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('Your account has been deactivated. Contact an administrator.'),
                'code' => 'ACCOUNT_INACTIVE',
                'errors' => ['email' => [__('Your account has been deactivated. Contact an administrator.')]],
            ], 403);
        }

        return back()->withErrors([
            'email' => __('Your account has been deactivated. Contact an administrator.'),
        ])->onlyInput('email');
    }

    private function invalidCredentialsResponse(LoginRequest $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => __('The provided credentials do not match our records.'),
                'errors' => ['email' => [__('The provided credentials do not match our records.')]],
            ], 422);
        }

        return back()->withErrors([
            'email' => __('The provided credentials do not match our records.'),
        ])->onlyInput('email');
    }
}
