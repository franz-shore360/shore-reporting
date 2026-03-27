<?php

namespace App\Repositories\Interfaces;

use App\Models\EmailLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

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

    public function find(int $id): ?EmailLog;

    public function delete(EmailLog $emailLog): bool;

    /**
     * @param  array<int, int>  $ids
     */
    public function deleteByIds(array $ids): int;
}
