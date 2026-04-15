<?php

namespace App\Http\Controllers;

use App\DataTable\Definitions\GlAccountDataTableDefinition;
use App\Http\Requests\StoreGlAccountRequest;
use App\Http\Requests\UpdateGlAccountRequest;
use App\Services\DataTableExportService;
use App\Services\DataTableService;
use App\Services\GlAccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GlAccountController extends Controller
{
    public function __construct(
        protected GlAccountService $glAccountService,
        protected DataTableService $dataTableService,
        protected DataTableExportService $dataTableExportService,
    ) {
        $this->middleware('permission:gl-account-list', ['only' => ['index', 'show', 'export']]);
        $this->middleware('permission:gl-account-create', ['only' => ['store']]);
        $this->middleware('permission:gl-account-edit', ['only' => ['update']]);
        $this->middleware('permission:gl-account-delete', ['only' => ['destroy']]);
    }

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->dataTableService->paginate(
            $request,
            $this->glAccountService,
            new GlAccountDataTableDefinition,
        );

        return response()->json($paginator);
    }

    public function export(Request $request): StreamedResponse
    {
        return $this->dataTableExportService->stream(
            $request,
            $this->glAccountService,
            new GlAccountDataTableDefinition,
            'gl-accounts',
        );
    }

    public function store(StoreGlAccountRequest $request): JsonResponse
    {
        $account = $this->glAccountService->create($request->validated());

        return response()->json($account, 201);
    }

    public function show(int $id): JsonResponse
    {
        $account = $this->glAccountService->findById($id);

        if ($account === null) {
            return response()->json(['message' => 'GL account not found'], 404);
        }

        return response()->json($account);
    }

    public function update(UpdateGlAccountRequest $request, int $id): JsonResponse
    {
        $account = $this->glAccountService->findById($id);

        if ($account === null) {
            return response()->json(['message' => 'GL account not found'], 404);
        }

        $account = $this->glAccountService->update($account, $request->validated());

        return response()->json($account);
    }

    public function destroy(int $id): JsonResponse
    {
        $account = $this->glAccountService->findById($id);

        if ($account === null) {
            return response()->json(['message' => 'GL account not found'], 404);
        }

        $this->glAccountService->delete($account);

        return response()->json(null, 204);
    }
}
