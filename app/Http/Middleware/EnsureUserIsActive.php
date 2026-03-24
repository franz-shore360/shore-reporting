<?php

namespace App\Http\Middleware;

use App\Services\AuthService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function __construct(
        protected AuthService $authService
    ) {}

    /**
     * Log out deactivated users and block access to the app.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->isActive()) {
            $this->authService->logoutWeb($request);

            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => __('Your account has been deactivated. Contact an administrator.'),
                    'code' => 'ACCOUNT_INACTIVE',
                ], 403);
            }

            return redirect('/login');
        }

        return $next($request);
    }
}
