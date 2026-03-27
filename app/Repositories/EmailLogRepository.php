<?php

namespace App\Repositories;

use App\Models\EmailLog;
use App\Repositories\Interfaces\EmailLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class EmailLogRepository implements EmailLogRepositoryInterface
{
    public function __construct(
        protected EmailLog $model
    ) {}

    /**
     * {@inheritdoc}
     */
    public function paginate(
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
     * @param  Builder<EmailLog>  $query
     */
    protected function newDataTableQuery(array $filters): Builder
    {
        $query = $this->model->newQuery()->select([
            'id',
            'sent_at',
            'from_address',
            'to_addresses',
            'cc_addresses',
            'bcc_addresses',
            'subject',
        ]);
        $this->applyEmailLogFilters($query, $filters);

        return $query;
    }

    /**
     * @param  Builder<EmailLog>  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyEmailLogFilters(Builder $query, array $filters): void
    {
        $id = isset($filters['id']) ? trim((string) $filters['id']) : '';
        if ($id !== '') {
            $query->whereRaw('CAST(email_logs.id AS CHAR) LIKE ?', ['%'.$id.'%']);
        }

        $subject = isset($filters['subject']) ? trim((string) $filters['subject']) : '';
        if ($subject !== '') {
            $query->where('email_logs.subject', 'like', '%'.$subject.'%');
        }

        $to = isset($filters['to_addresses']) ? trim((string) $filters['to_addresses']) : '';
        if ($to !== '') {
            $query->where('email_logs.to_addresses', 'like', '%'.$to.'%');
        }

        $from = isset($filters['from_address']) ? trim((string) $filters['from_address']) : '';
        if ($from !== '') {
            $query->where('email_logs.from_address', 'like', '%'.$from.'%');
        }
    }

    /**
     * @param  Builder<EmailLog>  $query
     */
    protected function applyDataTableOrder(Builder $query, string $sort, string $direction): void
    {
        $dir = $direction === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'subject', 'to_addresses', 'from_address', 'sent_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'sent_at';
        }

        match ($sort) {
            'subject' => $query->orderBy('email_logs.subject', $dir)->orderBy('email_logs.id', 'desc'),
            'to_addresses' => $query->orderBy('email_logs.to_addresses', $dir)->orderBy('email_logs.id', 'desc'),
            'from_address' => $query->orderBy('email_logs.from_address', $dir)->orderBy('email_logs.id', 'desc'),
            'sent_at' => $query->orderBy('email_logs.sent_at', $dir)->orderBy('email_logs.id', 'desc'),
            default => $query->orderBy('email_logs.id', $dir),
        };
    }
}
