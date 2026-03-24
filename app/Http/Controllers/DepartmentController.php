<?php

namespace App\Http\Controllers;

use App\DataTable\Definitions\DepartmentDataTableDefinition;
use App\Http\Requests\StoreDepartmentRequest;
use App\Http\Requests\UpdateDepartmentRequest;
use App\Services\DataTableService;
use App\Services\DepartmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function __construct(
        protected DepartmentService $departmentService,
        protected DataTableService $dataTableService,
    ) {
        $this->middleware('permission:department-list', ['only' => ['index', 'show']]);
        $this->middleware('permission:department-create', ['only' => ['store']]);
        $this->middleware('permission:department-edit', ['only' => ['update']]);
        $this->middleware('permission:department-delete', ['only' => ['destroy']]);
    }

    /**
     * Department list for profile / user forms (any authenticated user).
     */
    public function options(): JsonResponse
    {
        $departments = $this->departmentService->getAllDepartments();

        return response()->json($departments);
    }

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->dataTableService->paginate(
            $request,
            $this->departmentService,
            new DepartmentDataTableDefinition,
        );

        return response()->json($paginator);
    }

    public function store(StoreDepartmentRequest $request): JsonResponse
    {
        $department = $this->departmentService->createDepartment($request->validated());

        return response()->json($department, 201);
    }

    public function show(int $id): JsonResponse
    {
        $department = $this->departmentService->getDepartmentById($id);

        if ($department === null) {
            return response()->json(['message' => 'Department not found'], 404);
        }

        return response()->json($department);
    }

    public function update(UpdateDepartmentRequest $request, int $id): JsonResponse
    {
        $department = $this->departmentService->updateDepartment($id, $request->validated());

        if ($department === null) {
            return response()->json(['message' => 'Department not found'], 404);
        }

        return response()->json($department);
    }

    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->departmentService->deleteDepartment($id);

        if (! $deleted) {
            return response()->json(['message' => 'Department not found'], 404);
        }

        return response()->json(null, 204);
    }
}
