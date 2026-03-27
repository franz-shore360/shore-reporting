<?php

namespace App\Repositories\Interfaces;

use App\Models\EmailLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\LazyCollection;

interface EmailLogRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(
        int $perPage,
        int $page,
        string $sort,
        string $direction,
        array $filters = [],
    ): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $filters
     * @return LazyCollection<int, EmailLog>
     */
    public function cursorForDataTableExport(
        string $sort,
        string $direction,
        array $filters = [],
    ): LazyCollection;
}
