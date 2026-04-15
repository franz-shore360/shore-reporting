<?php

namespace App\Services;

use App\Contracts\DataTable\DataTableQueryable;
use App\Models\Import;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportService implements DataTableQueryable
{
    private const IMPORT_STORAGE_DIR = 'imports';

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginateForDataTable(
        int $perPage,
        int $page,
        string $sort,
        string $direction,
        array $filters = [],
    ): LengthAwarePaginator {
        $query = $this->newDataTableQuery($filters);
        $this->applyDataTableOrder($query, $sort, $direction);

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * {@inheritdoc}
     */
    public function iterateRowsForDataTableExport(
        string $sort,
        string $direction,
        array $filters = [],
    ): iterable {
        $query = $this->newDataTableQuery($filters);
        $this->applyDataTableOrder($query, $sort, $direction);

        foreach ($query->cursor() as $import) {
            yield [
                'id' => $import->id,
                'entity_type' => $import->entity_type,
                'import_file' => $import->import_file,
                'error_file' => $import->error_file ?? '',
                'status' => $import->status,
                'total_items' => $import->total_items ?? '',
                'total_errors' => $import->total_errors ?? '',
                'email_sent' => $import->email_sent ? 'Yes' : 'No',
                'user_name' => $import->user?->full_name ?? '',
                'created_at' => $import->created_at?->format('Y-m-d H:i:s') ?? '',
                'started_at' => $import->started_at?->format('Y-m-d H:i:s') ?? '',
                'completed_at' => $import->completed_at?->format('Y-m-d H:i:s') ?? '',
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Import>
     */
    protected function newDataTableQuery(array $filters): Builder
    {
        $query = Import::query()->with('user:id,first_name,middle_name,last_name,email');

        $id = isset($filters['id']) ? trim((string) $filters['id']) : '';
        if ($id !== '') {
            $query->whereRaw('CAST(imports.id AS CHAR) LIKE ?', ['%'.$id.'%']);
        }

        $entityType = isset($filters['entity_type']) ? trim((string) $filters['entity_type']) : '';
        if ($entityType !== '') {
            $query->where('imports.entity_type', 'like', '%'.$entityType.'%');
        }

        $status = isset($filters['status']) ? trim((string) $filters['status']) : '';
        if ($status !== '') {
            $query->where('imports.status', 'like', '%'.$status.'%');
        }

        $importFile = isset($filters['import_file']) ? trim((string) $filters['import_file']) : '';
        if ($importFile !== '') {
            $query->where('imports.import_file', 'like', '%'.$importFile.'%');
        }

        $emailSent = $filters['email_sent'] ?? '';
        if ($emailSent === 'Yes' || $emailSent === '1' || $emailSent === 1 || $emailSent === true) {
            $query->where('imports.email_sent', true);
        } elseif ($emailSent === 'No' || $emailSent === '0' || $emailSent === 0 || $emailSent === false) {
            $query->where('imports.email_sent', false);
        }

        return $query;
    }

    /**
     * @param  Builder<Import>  $query
     */
    protected function applyDataTableOrder(Builder $query, string $sort, string $direction): void
    {
        $dir = $direction === 'desc' ? 'desc' : 'asc';

        $allowedSorts = [
            'id', 'entity_type', 'status', 'total_items', 'total_errors',
            'email_sent', 'user_id', 'created_at', 'started_at', 'completed_at',
        ];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        match ($sort) {
            'entity_type' => $query->orderBy('imports.entity_type', $dir)->orderBy('imports.id', 'desc'),
            'status' => $query->orderBy('imports.status', $dir)->orderBy('imports.id', 'desc'),
            'total_items' => $query->orderBy('imports.total_items', $dir)->orderBy('imports.id', 'desc'),
            'total_errors' => $query->orderBy('imports.total_errors', $dir)->orderBy('imports.id', 'desc'),
            'email_sent' => $query->orderBy('imports.email_sent', $dir)->orderBy('imports.id', 'desc'),
            'user_id' => $query->orderBy('imports.user_id', $dir)->orderBy('imports.id', 'desc'),
            'created_at' => $query->orderBy('imports.created_at', $dir)->orderBy('imports.id', 'desc'),
            'started_at' => $query->orderBy('imports.started_at', $dir)->orderBy('imports.id', 'desc'),
            'completed_at' => $query->orderBy('imports.completed_at', $dir)->orderBy('imports.id', 'desc'),
            default => $query->orderBy('imports.id', $dir),
        };
    }

    /**
     * @return array{import: Import, stored_path: string}
     */
    public function storeUpload(User $user, string $entityType, UploadedFile $file): array
    {
        $disk = Storage::disk('local');
        $disk->makeDirectory(self::IMPORT_STORAGE_DIR);

        $safeBasename = $this->sanitizeOriginalBasename($file->getClientOriginalName());
        $safeBasename = $this->ensureAllowedExtension($file, $safeBasename);
        $uniqueName = $this->uniqueFilenameInImports($disk, $safeBasename);
        $path = $file->storeAs(self::IMPORT_STORAGE_DIR, $uniqueName, 'local');
        $dbImportPath = $path;
        $prefix = self::IMPORT_STORAGE_DIR.'/';
        if (str_starts_with($dbImportPath, $prefix)) {
            $dbImportPath = substr($dbImportPath, strlen($prefix));
        }

        $import = Import::query()->create([
            'user_id' => $user->id,
            'entity_type' => $entityType,
            'import_file' => $dbImportPath,
            'error_file' => null,
            'status' => Import::STATUS_PENDING,
            'total_items' => null,
            'total_errors' => null,
            'email_sent' => false,
            'started_at' => null,
            'completed_at' => null,
        ]);

        return ['import' => $import, 'stored_path' => $dbImportPath];
    }

    /**
     * Strip path components and unsafe characters; keep letters, numbers, spaces, dot, dash, underscore.
     */
    protected function sanitizeOriginalBasename(string $originalName): string
    {
        $base = basename(str_replace("\0", '', $originalName));
        $base = preg_replace('/[^\p{L}\p{N}\s._\-]+/u', '_', $base) ?? '';
        $base = trim((string) preg_replace('/_+/', '_', $base), ' _.');

        if ($base === '' || $base === '.' || $base === '..') {
            return 'upload';
        }

        if (strlen($base) > 220) {
            $info = pathinfo($base);
            $ext = isset($info['extension']) && $info['extension'] !== '' ? '.'.$info['extension'] : '';
            $stem = (string) ($info['filename'] ?? 'upload');
            $maxStem = max(1, 220 - strlen($ext));

            return substr($stem, 0, $maxStem).$ext;
        }

        return $base;
    }

    /**
     * If the basename has no extension, append the client extension when it is one of the allowed import types.
     */
    protected function ensureAllowedExtension(UploadedFile $file, string $basename): string
    {
        $extFromName = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
        $allowed = ['csv', 'txt', 'xlsx', 'xls'];
        if ($extFromName !== '' && in_array($extFromName, $allowed, true)) {
            return $basename;
        }

        $clientExt = strtolower((string) $file->getClientOriginalExtension());
        if ($clientExt !== '' && in_array($clientExt, $allowed, true)) {
            $stem = pathinfo($basename, PATHINFO_FILENAME) ?: 'upload';

            return $stem.'.'.$clientExt;
        }

        return $basename;
    }

    /**
     * Use the sanitized basename; if the file already exists under storage/app/imports, append _1, _2, … before the extension.
     */
    protected function uniqueFilenameInImports(Filesystem $disk, string $filename): string
    {
        $dir = self::IMPORT_STORAGE_DIR;
        $relative = $dir.'/'.$filename;

        if (! $disk->exists($relative)) {
            return $filename;
        }

        $info = pathinfo($filename);
        $stem = (string) ($info['filename'] ?? 'upload');
        $ext = isset($info['extension']) && $info['extension'] !== '' ? '.'.$info['extension'] : '';

        for ($n = 1; $n < 10_000; $n++) {
            $candidate = $stem.'_'.$n.$ext;
            if (! $disk->exists($dir.'/'.$candidate)) {
                return $candidate;
            }
        }

        return $stem.'_'.Str::random(8).$ext;
    }
}
