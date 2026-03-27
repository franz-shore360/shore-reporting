<?php

namespace App\Services;

use App\Contracts\DataTable\DataTableQueryable;
use App\Models\EmailLog;
use App\Repositories\Interfaces\EmailLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EmailLogService implements DataTableQueryable
{
    public function __construct(
        protected EmailLogRepositoryInterface $emailLogRepository
    ) {}

    public function deleteEmailLog(EmailLog $emailLog): bool
    {
        return $this->emailLogRepository->delete($emailLog);
    }

    /**
     * @param  array<int, int>  $ids
     * @return array{deleted: int, skipped_missing: int}
     */
    public function deleteEmailLogsByIds(array $ids): array
    {
        $unique = array_values(array_unique(array_map('intval', $ids)));
        if ($unique === []) {
            return ['deleted' => 0, 'skipped_missing' => 0];
        }

        $deleted = $this->emailLogRepository->deleteByIds($unique);
        $skippedMissing = count($unique) - $deleted;

        return [
            'deleted' => $deleted,
            'skipped_missing' => max(0, $skippedMissing),
        ];
    }

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
        return $this->emailLogRepository->paginate($perPage, $page, $sort, $direction, $filters);
    }

    /**
     * {@inheritdoc}
     */
    public function iterateRowsForDataTableExport(
        string $sort,
        string $direction,
        array $filters = [],
    ): iterable {
        return new \EmptyIterator;
    }
}
