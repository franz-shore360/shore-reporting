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
}
