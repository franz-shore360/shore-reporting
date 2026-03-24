<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Aggregate counts for the dashboard (any authenticated user).
     */
    public function stats(): JsonResponse
    {
        return response()->json([
            'users_count' => User::count(),
            'departments_count' => Department::count(),
        ]);
    }
}
