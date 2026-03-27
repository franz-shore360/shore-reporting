<?php

namespace App\Http\Controllers;

use App\DataTable\Definitions\EmailLogDataTableDefinition;
use App\Http\Requests\BulkDestroyEmailLogsRequest;
use App\Models\EmailLog;
use App\Services\DataTableService;
use App\Services\EmailLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailLogController extends Controller
{
    public function __construct(
        protected EmailLogService $emailLogService,
        protected DataTableService $dataTableService,
    ) {
        $this->middleware('permission:email-log-list', ['only' => ['index', 'show']]);
        $this->middleware('permission:email-log-delete', ['only' => ['destroy', 'bulkDestroy']]);
    }

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->dataTableService->paginate(
            $request,
            $this->emailLogService,
            new EmailLogDataTableDefinition,
        );

        return response()->json($paginator);
    }

    public function show(EmailLog $emailLog): JsonResponse
    {
        return response()->json($emailLog);
    }

    public function destroy(EmailLog $emailLog): JsonResponse
    {
        if (! $this->emailLogService->deleteEmailLog($emailLog)) {
            return response()->json(['message' => 'Email log not found'], 404);
        }

        return response()->json(null, 204);
    }

    public function bulkDestroy(BulkDestroyEmailLogsRequest $request): JsonResponse
    {
        /** @var array<int, int> $ids */
        $ids = $request->validated('ids');
        $result = $this->emailLogService->deleteEmailLogsByIds($ids);

        return response()->json($result);
    }
}
