<?php

namespace App\Http\Controllers;

use App\DataTable\Definitions\ImportDataTableDefinition;
use App\Http\Requests\StoreImportRequest;
use App\Models\Import;
use App\Services\DataTableExportService;
use App\Services\DataTableService;
use App\Services\ImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportController extends Controller
{
    public function __construct(
        protected ImportService $importService,
        protected DataTableService $dataTableService,
        protected DataTableExportService $dataTableExportService,
    ) {
        $this->middleware('permission:import-list', ['only' => ['index', 'export', 'downloadFile', 'downloadErrorFile']]);
        $this->middleware('permission:import-create', ['only' => ['store']]);
    }

    public function index(Request $request): JsonResponse
    {
        $paginator = $this->dataTableService->paginate(
            $request,
            $this->importService,
            new ImportDataTableDefinition,
        );

        return response()->json($paginator);
    }

    public function export(Request $request): StreamedResponse
    {
        return $this->dataTableExportService->stream(
            $request,
            $this->importService,
            new ImportDataTableDefinition,
            'imports',
        );
    }

    public function downloadFile(Import $import): BinaryFileResponse
    {
        $relative = $import->importFileDiskRelative();
        if ($relative === null || $relative === '' || str_contains($relative, '..')) {
            abort(404);
        }

        if (! str_starts_with($relative, Import::IMPORT_STORAGE_DISK_PREFIX.'/')) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($relative)) {
            abort(404);
        }

        $absolute = Storage::disk('local')->path($relative);
        $downloadName = basename($relative);

        return response()->download($absolute, $downloadName);
    }

    public function downloadErrorFile(Import $import): BinaryFileResponse
    {
        $relative = $import->errorFileDiskRelative();
        if ($relative === null || $relative === '' || str_contains($relative, '..')) {
            abort(404);
        }

        if (! str_starts_with($relative, Import::IMPORT_STORAGE_DISK_PREFIX.'/errors/')) {
            abort(404);
        }

        if (! str_starts_with($relative, Import::IMPORT_STORAGE_DISK_PREFIX.'/errors/'.$import->id.'_')) {
            abort(404);
        }

        if (! Storage::disk('local')->exists($relative)) {
            abort(404);
        }

        $absolute = Storage::disk('local')->path($relative);
        $downloadName = basename($relative);

        return response()->download($absolute, $downloadName);
    }

    public function store(StoreImportRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();
        $file = $request->file('file');
        if ($file === null) {
            return response()->json(['message' => 'A file is required.'], 422);
        }

        $result = $this->importService->storeUpload(
            $user,
            $validated['entity_type'],
            $file,
        );

        $import = $result['import']->load('user:id,first_name,middle_name,last_name,email');

        return response()->json($import, 201);
    }
}
