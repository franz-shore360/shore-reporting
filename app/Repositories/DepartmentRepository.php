<?php

namespace App\Repositories;

use App\Models\Department;
use App\Repositories\Interfaces\DepartmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

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
        $dir = $direction === 'desc' ? 'desc' : 'asc';
        $query = $this->model->newQuery();

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

        $allowedSorts = ['id', 'name', 'is_active', 'created_at', 'updated_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        match ($sort) {
            'name' => $query->orderBy('name', $dir)->orderBy('id', 'desc'),
            'is_active' => $query->orderBy('is_active', $dir)->orderBy('id', 'desc'),
            'created_at' => $query->orderBy('created_at', $dir)->orderBy('id', 'desc'),
            'updated_at' => $query->orderBy('updated_at', $dir)->orderBy('id', 'desc'),
            default => $query->orderBy('id', $dir),
        };

        return $query->paginate($perPage, ['*'], 'page', $page);
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
