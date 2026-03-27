<?php

namespace App\Http\Controllers;

use App\DataTable\Definitions\UserDataTableDefinition;
use App\Http\Requests\BulkUpdateUsersDepartmentRequest;
use App\Http\Requests\BulkDestroyUsersRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Services\DataTableExportService;
use App\Services\DataTableService;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function __construct(
        protected UserService $userService,
        protected DataTableService $dataTableService,
        protected DataTableExportService $dataTableExportService,
    ) {
        $this->middleware('permission:user-list', ['only' => ['index', 'show', 'export']]);
        $this->middleware('permission:user-create', ['only' => ['store']]);
        $this->middleware('permission:user-edit', ['only' => ['update', 'bulkUpdateDepartment']]);
        $this->middleware('permission:user-delete', ['only' => ['destroy', 'bulkDestroy']]);
    }

    /**
     * Display a listing of the resource (paginated).
     */
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->dataTableService->paginate(
            $request,
            $this->userService,
            new UserDataTableDefinition,
        );

        return response()->json($paginator);
    }

    /**
     * Export the current grid result set (filters + sort) as CSV or Excel.
     */
    public function export(Request $request): StreamedResponse
    {
        return $this->dataTableExportService->stream(
            $request,
            $this->userService,
            new UserDataTableDefinition,
            'users',
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $data = $request->validated();
        $role = $data['role'] ?? null;
        unset($data['role']);
        $user = $this->userService->createUser($data);
        if ($role) {
            $user->assignRole($role);
        }

        return response()->json($user->load('roles'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id): JsonResponse
    {
        $user = $this->userService->getUserById($id);

        if ($user === null) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();
        $role = $data['role'] ?? null;
        unset($data['role'], $data['profile_image']);

        $targetUser = $this->userService->getUserById($id);
        if ($targetUser !== null
            && $targetUser->hasRole(RoleController::ADMIN_ROLE_NAME)
            && ! $request->user()->hasRole(RoleController::ADMIN_ROLE_NAME)
        ) {
            unset($data['is_active']);
        }

        if (array_key_exists('remove_profile_image', $data)) {
            $data['remove_profile_image'] = filter_var($data['remove_profile_image'], FILTER_VALIDATE_BOOLEAN);
        }
        if (! empty($data['remove_profile_image'])) {
            $user = $this->userService->getUserById($id);
            if ($user && $user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }
            $data['profile_image'] = null;
            unset($data['remove_profile_image']);
        }

        if ($request->hasFile('profile_image')) {
            $user = $this->userService->getUserById($id);
            if ($user && $user->profile_image) {
                Storage::disk('public')->delete($user->profile_image);
            }
            $data['profile_image'] = $request->file('profile_image')->store('profile-images', 'public');
        }

        $user = $this->userService->updateUser($id, $data);

        if ($user === null) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $user->syncRoles($role ? [$role] : []);

        return response()->json($user->fresh()->load('roles'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->userService->deleteUser($id);

        if (! $deleted) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json(null, 204);
    }

    /**
     * Remove multiple users (same permission as single delete).
     */
    public function bulkDestroy(BulkDestroyUsersRequest $request): JsonResponse
    {
        /** @var array<int, int> $ids */
        $ids = $request->validated('ids');
        $authId = (int) $request->user()->getAuthIdentifier();

        $result = $this->userService->deleteUsers($ids, $authId);

        return response()->json($result);
    }

    /**
     * Update department for many users (or clear department when department_id is null).
     */
    public function bulkUpdateDepartment(BulkUpdateUsersDepartmentRequest $request): JsonResponse
    {
        /** @var array<int, int> $ids */
        $ids = $request->validated('ids');
        $departmentId = $request->validated('department_id');

        $updated = $this->userService->updateDepartmentForUsers($ids, $departmentId);

        return response()->json([
            'updated' => $updated,
        ]);
    }
}
