<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    /**
     * Return grouped permissions from config (for role form checkboxes).
     */
    public function index(): JsonResponse
    {
        return response()->json(config('permissions', []));
    }
}
