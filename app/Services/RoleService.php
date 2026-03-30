<?php

namespace App\Services;

use App\Contracts\DataTable\DataTableQueryable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use App\Models\Role;

class RoleService implements DataTableQueryable
{
    private const GUARD_NAME = 'web';

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

        foreach ($query->cursor() as $role) {
            yield [
                'id' => $role->id,
                'name' => $role->name,
                'users_count' => (int) ($role->users_count ?? 0),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Role>
     */
    protected function newDataTableQuery(array $filters): Builder
    {
        $query = Role::query()
            ->where('guard_name', self::GUARD_NAME)
            ->withCount('users');

        $id = isset($filters['id']) ? trim((string) $filters['id']) : '';
        if ($id !== '') {
            $query->whereRaw('CAST(roles.id AS CHAR) LIKE ?', ['%'.$id.'%']);
        }

        $name = isset($filters['name']) ? trim((string) $filters['name']) : '';
        if ($name !== '') {
            $query->where('roles.name', 'like', '%'.$name.'%');
        }

        return $query;
    }

    /**
     * @param  Builder<Role>  $query
     */
    protected function applyDataTableOrder(Builder $query, string $sort, string $direction): void
    {
        $dir = $direction === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'name', 'users_count'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        if ($sort === 'name') {
            $query->orderBy('name', $dir)->orderBy('id', 'desc');
        } elseif ($sort === 'users_count') {
            $query->orderBy('users_count', $dir)->orderBy('id', 'desc');
        } else {
            $query->orderBy('id', $dir);
        }
    }

    /**
     * @return Collection<int, Role>
     */
    public function listOptions(): Collection
    {
        return Role::query()
            ->where('guard_name', self::GUARD_NAME)
            ->orderBy('name')
            ->get(['id', 'name']);
    }
}
