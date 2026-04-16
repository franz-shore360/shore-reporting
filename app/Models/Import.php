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

    /** Files live under storage/app/imports; DB columns omit this prefix (legacy rows may still include it). */
    public const IMPORT_STORAGE_DISK_PREFIX = 'imports';

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
     */
    public function importFileDiskRelative(): ?string
    {
        $stored = $this->import_file;
        if ($stored === null || $stored === '') {
            return null;
        }
        if (str_starts_with($stored, self::IMPORT_STORAGE_DISK_PREFIX.'/')) {
            return $stored;
        }

        return self::IMPORT_STORAGE_DISK_PREFIX.'/'.ltrim($stored, '/');
    }

    /**
     * Path relative to the {@code local} disk root (storage/app) for the row-level error export.
     */
    public function errorFileDiskRelative(): ?string
    {
        $stored = $this->error_file;
        if ($stored === null || $stored === '') {
            return null;
        }
        if (str_starts_with($stored, self::IMPORT_STORAGE_DISK_PREFIX.'/')) {
            return $stored;
        }

        return self::IMPORT_STORAGE_DISK_PREFIX.'/'.ltrim($stored, '/');
    }

    /**
     * Relative path for the row-error export file as stored in {@see Import::$error_file}
     * (under {@see Import::IMPORT_STORAGE_DISK_PREFIX}/ on disk), e.g. {@code errors/myfile_errors.csv}.
     */
    public function canonicalErrorFileStorageRelative(): string
    {
        $stored = (string) ($this->import_file ?? '');
        $basename = basename(str_replace('\\', '/', $stored));
        $extension = strtolower(pathinfo($basename, PATHINFO_EXTENSION));
        $stem = pathinfo($basename, PATHINFO_FILENAME);
        $stem = is_string($stem) && $stem !== ''
            ? (string) preg_replace('/[^A-Za-z0-9._-]+/', '_', $stem)
            : 'import';

        return 'errors/'.$stem.'_errors.'.$extension;
    }
}
