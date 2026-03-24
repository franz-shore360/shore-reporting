<?php

namespace App\Contracts\DataTable;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Domain services that expose a paginated list for data-table APIs.
 */
interface DataTableQueryable
{
    /**
     * @param  array<string, mixed>  $filters  Keys match repository expectations (e.g. id, email).
     */
    public function paginateForDataTable(
        int $perPage,
        int $page,
        string $sort,
        string $direction,
        array $filters = [],
    ): LengthAwarePaginator;
}
