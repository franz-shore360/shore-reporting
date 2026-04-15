<?php

namespace App\Services;

use App\Contracts\DataTable\DataTableQueryable;
use App\Models\GlAccount;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class GlAccountService implements DataTableQueryable
{
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

        foreach ($query->cursor() as $account) {
            yield [
                'id' => $account->id,
                'name' => $account->name,
                'code' => $account->code,
                'created_at' => $account->created_at?->format('Y-m-d H:i:s') ?? '',
                'updated_at' => $account->updated_at?->format('Y-m-d H:i:s') ?? '',
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<GlAccount>
     */
    protected function newDataTableQuery(array $filters): Builder
    {
        $query = GlAccount::query();

        $id = isset($filters['id']) ? trim((string) $filters['id']) : '';
        if ($id !== '') {
            $query->whereRaw('CAST(gl_accounts.id AS CHAR) LIKE ?', ['%'.$id.'%']);
        }

        $name = isset($filters['name']) ? trim((string) $filters['name']) : '';
        if ($name !== '') {
            $query->where('gl_accounts.name', 'like', '%'.$name.'%');
        }

        $code = isset($filters['code']) ? trim((string) $filters['code']) : '';
        if ($code !== '') {
            $query->where('gl_accounts.code', 'like', '%'.$code.'%');
        }

        return $query;
    }

    /**
     * @param  Builder<GlAccount>  $query
     */
    protected function applyDataTableOrder(Builder $query, string $sort, string $direction): void
    {
        $dir = $direction === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'name', 'code', 'created_at', 'updated_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        match ($sort) {
            'name' => $query->orderBy('name', $dir)->orderBy('id', 'desc'),
            'code' => $query->orderBy('code', $dir)->orderBy('id', 'desc'),
            'created_at' => $query->orderBy('created_at', $dir)->orderBy('id', 'desc'),
            'updated_at' => $query->orderBy('updated_at', $dir)->orderBy('id', 'desc'),
            default => $query->orderBy('id', $dir),
        };
    }

    public function findById(int $id): ?GlAccount
    {
        return GlAccount::query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): GlAccount
    {
        return GlAccount::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(GlAccount $account, array $data): GlAccount
    {
        $account->update($data);

        return $account->fresh();
    }

    public function delete(GlAccount $account): bool
    {
        return (bool) $account->delete();
    }
}
