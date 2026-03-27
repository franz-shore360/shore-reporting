<?php

namespace App\Http\Controllers;

use App\DataTable\Definitions\EmailLogDataTableDefinition;
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
        $this->middleware('permission:email-log-list');
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
}
