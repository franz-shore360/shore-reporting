<?php

namespace App\Services;

use App\Contracts\DataTable\DataTableQueryable;
use App\Repositories\Interfaces\EmailLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class EmailLogService implements DataTableQueryable
{
    public function __construct(
        protected EmailLogRepositoryInterface $emailLogRepository
    ) {}

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
        foreach ($this->emailLogRepository->cursorForDataTableExport($sort, $direction, $filters) as $log) {
            yield [
                'id' => $log->id,
                'subject' => $log->subject ?? '',
                'to_addresses' => $log->to_addresses ?? '',
                'from_address' => $log->from_address ?? '',
                'sent_at' => $log->sent_at?->format('Y-m-d H:i:s') ?? '',
            ];
        }
    }
}
