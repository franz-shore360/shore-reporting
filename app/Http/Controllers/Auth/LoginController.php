<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
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
        if (Auth::attempt($request->only('email', 'password'), $request->boolean('remember'))) {
            $user = Auth::user();

            if (! $user->isActive()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

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

            $request->session()->regenerate();

            if ($request->expectsJson()) {
                return response()->json(['user' => Auth::user()]);
            }

            return redirect()->intended(route('home'));
        }

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

    /**
     * Log the user out.
     *
     * @return RedirectResponse|JsonResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Logged out']);
        }

        return redirect()->route('login');
    }
}
