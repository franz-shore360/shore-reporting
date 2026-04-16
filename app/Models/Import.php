<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Import extends Model
{
    public const UPDATED_AT = null;

    public const ENTITY_DEPARTMENT = 'department';

    public const ENTITY_GL_ACCOUNT = 'gl_account';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    /**
     * Path relative to the {@code local} disk root (storage/app) for uploaded import files (must end with /).
     */
    public const IMPORT_FILE_BASE_PATH = 'imports/';

    /**
     * Path relative to the {@code local} disk root (storage/app) for per-import row error files (must end with /).
     */
    public const ERROR_FILE_BASE_PATH = 'imports/errors/';

    /**
     * @var list<string>
     */
    public const ENTITY_TYPES = [
        self::ENTITY_DEPARTMENT,
        self::ENTITY_GL_ACCOUNT,
    ];

    protected $fillable = [
        'user_id',
        'entity_type',
        'import_file',
        'error_file',
        'status',
        'total_items',
        'total_errors',
        'email_sent',
        'started_at',
        'completed_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'email_sent' => 'boolean',
        'created_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Path relative to the {@code local} disk root (storage/app) for the uploaded import file.
     *
     * {@see IMPORT_FILE_BASE_PATH} is the canonical prefix; DB {@see Import::$import_file} usually omits it.
     */
    public function importFileDiskRelative(): ?string
    {
        $stored = $this->import_file;
        if ($stored === null || $stored === '') {
            return null;
        }
        if (str_starts_with($stored, self::IMPORT_FILE_BASE_PATH)) {
            return $stored;
        }

        return self::IMPORT_FILE_BASE_PATH.ltrim($stored, '/');
    }

    /**
     * Path relative to the {@code local} disk root (storage/app) for the row-level error file.
     *
     * {@see Import::$error_file} stores the file basename only (e.g. {@code myfile_errors.csv}), or a path
     * already prefixed with {@see IMPORT_FILE_BASE_PATH}.
     */
    public function errorFileDiskRelative(): ?string
    {
        $stored = $this->error_file;
        if ($stored === null || $stored === '') {
            return null;
        }

        $stored = str_replace('\\', '/', trim($stored));
        if (str_contains($stored, '..')) {
            return null;
        }

        if (str_starts_with($stored, self::IMPORT_FILE_BASE_PATH)) {
            return $stored;
        }

        $basename = basename($stored);
        if ($basename === '' || $basename === '.' || $basename === '..') {
            return null;
        }

        return self::ERROR_FILE_BASE_PATH.$basename;
    }

    /**
     * Basename for the row-error file as stored in {@see Import::$error_file} (no directory segments).
     */
    public function canonicalErrorFileBasename(): string
    {
        $stored = (string) ($this->import_file ?? '');
        $basename = basename(str_replace('\\', '/', $stored));
        $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
        $stem = pathinfo($basename, PATHINFO_FILENAME);
        $stem = is_string($stem) && $stem !== ''
            ? (string) preg_replace('/[^A-Za-z0-9._-]+/', '_', $stem)
            : 'import';

        return $stem.'_errors.'.$extension;
    }
}
