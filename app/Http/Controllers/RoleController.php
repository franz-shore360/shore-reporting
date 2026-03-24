<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    private const GUARD_NAME = 'web';

    /** Role name that cannot be deleted. */
    public const ADMIN_ROLE_NAME = 'Admin';

    public function __construct()
    {
        $this->middleware('role:'.self::ADMIN_ROLE_NAME, ['only' => ['show', 'store', 'update', 'destroy']]);
    }

    /**
     * List all roles (for dropdowns/filters).
     */
    public function index(): JsonResponse
    {
        $roles = Role::where('guard_name', self::GUARD_NAME)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($roles);
    }

    /**
     * Show a role with its permissions (for edit form).
     */
    public function show(int $id): JsonResponse
    {
        $role = Role::where('guard_name', self::GUARD_NAME)->find($id);

        if ($role === null) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        $role->load('permissions');
        $role->permission_names = $role->permissions->pluck('name')->values()->all();

        return response()->json($role);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => self::GUARD_NAME,
        ]);

        $permissions = $request->validated('permissions', []);
        if (! empty($permissions)) {
            $role->syncPermissions($permissions);
        }

        return response()->json($role->load('permissions'), 201);
    }

    public function update(UpdateRoleRequest $request, int $id): JsonResponse
    {
        $role = Role::where('guard_name', self::GUARD_NAME)->find($id);

        if ($role === null) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        if ($role->name === self::ADMIN_ROLE_NAME) {
            return response()->json(
                ['message' => 'The Admin role cannot be edited.'],
                422
            );
        }

        $role->update(['name' => $request->validated('name')]);
        $role->syncPermissions($request->validated('permissions', []));

        return response()->json($role->fresh()->load('permissions'));
    }

    public function destroy(int $id): JsonResponse
    {
        $role = Role::where('guard_name', self::GUARD_NAME)->find($id);

        if ($role === null) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        if ($role->name === self::ADMIN_ROLE_NAME) {
            return response()->json(
                ['message' => 'The Admin role cannot be deleted.'],
                422
            );
        }

        $role->delete();

        return response()->json(null, 204);
    }
}
