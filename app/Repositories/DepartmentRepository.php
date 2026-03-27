<?php

namespace App\Repositories;

use App\Models\Department;
use App\Repositories\Interfaces\DepartmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;

class DepartmentRepository implements DepartmentRepositoryInterface
{
    protected Department $model;

    public function __construct(Department $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): Collection
    {
        return $this->model->newQuery()->orderBy('name')->get();
    }

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
     * {@inheritdoc}
     */
    public function cursorForDataTableExport(string $sort, string $direction, array $filters = []): LazyCollection
    {
        $query = $this->newDataTableQuery($filters);
        $this->applyDataTableOrder($query, $sort, $direction);

        return $query->cursor();
    }

    /**
     * @param  Builder<Department>  $query
     */
    protected function newDataTableQuery(array $filters): Builder
    {
        $query = $this->model->newQuery()->withCount('users');
        $this->applyDepartmentFilters($query, $filters);

        return $query;
    }

    /**
     * @param  Builder<Department>  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyDepartmentFilters(Builder $query, array $filters): void
    {
        $id = isset($filters['id']) ? trim((string) $filters['id']) : '';
        if ($id !== '') {
            $query->whereRaw('CAST(departments.id AS CHAR) LIKE ?', ['%'.$id.'%']);
        }

        $name = isset($filters['name']) ? trim((string) $filters['name']) : '';
        if ($name !== '') {
            $query->where('departments.name', 'like', '%'.$name.'%');
        }

        $isActive = $filters['is_active'] ?? '';
        if ($isActive === 'Active' || $isActive === '1' || $isActive === 1 || $isActive === true) {
            $query->where('departments.is_active', true);
        } elseif ($isActive === 'Inactive' || $isActive === '0' || $isActive === 0 || $isActive === false) {
            $query->where('departments.is_active', false);
        }
    }

    /**
     * @param  Builder<Department>  $query
     */
    protected function applyDataTableOrder(Builder $query, string $sort, string $direction): void
    {
        $dir = $direction === 'desc' ? 'desc' : 'asc';

        $allowedSorts = ['id', 'name', 'is_active', 'users_count', 'created_at', 'updated_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        match ($sort) {
            'name' => $query->orderBy('name', $dir)->orderBy('id', 'desc'),
            'is_active' => $query->orderBy('is_active', $dir)->orderBy('id', 'desc'),
            'users_count' => $query->orderBy('users_count', $dir)->orderBy('id', 'desc'),
            'created_at' => $query->orderBy('created_at', $dir)->orderBy('id', 'desc'),
            'updated_at' => $query->orderBy('updated_at', $dir)->orderBy('id', 'desc'),
            default => $query->orderBy('id', $dir),
        };
    }

    /**
     * {@inheritdoc}
     */
    public function find(int $id): ?Department
    {
        return $this->model->newQuery()->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Department
    {
        return $this->model->newQuery()->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(Department $department, array $data): bool
    {
        return $department->update($data);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Department $department): bool
    {
        return $department->delete();
    }
}
