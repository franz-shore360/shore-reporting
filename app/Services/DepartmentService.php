<?php

namespace App\Services;

use App\Contracts\DataTable\DataTableQueryable;
use App\Models\Department;
use App\Repositories\Interfaces\DepartmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class DepartmentService implements DataTableQueryable
{
    public function __construct(
        protected DepartmentRepositoryInterface $departmentRepository
    ) {}

    /**
     * Get all departments.
     *
     * @return Collection<int, Department>
     */
    public function getAllDepartments(): Collection
    {
        return $this->departmentRepository->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getDepartmentsPaginated(
        int $perPage,
        int $page,
        string $sort,
        string $direction,
        array $filters = [],
    ): LengthAwarePaginator {
        return $this->departmentRepository->paginate($perPage, $page, $sort, $direction, $filters);
    }

    /**
     * {@inheritdoc}
     */
    public function paginateForDataTable(
        int $perPage,
        int $page,
        string $sort,
        string $direction,
        array $filters = [],
    ): LengthAwarePaginator {
        return $this->getDepartmentsPaginated($perPage, $page, $sort, $direction, $filters);
    }

    /**
     * {@inheritdoc}
     */
    public function iterateRowsForDataTableExport(
        string $sort,
        string $direction,
        array $filters = [],
    ): iterable {
        foreach ($this->departmentRepository->cursorForDataTableExport($sort, $direction, $filters) as $department) {
            yield [
                'id' => $department->id,
                'name' => $department->name,
                'is_active' => $department->is_active ? 'Active' : 'Inactive',
                'created_at' => $department->created_at?->format('Y-m-d H:i:s') ?? '',
                'updated_at' => $department->updated_at?->format('Y-m-d H:i:s') ?? '',
            ];
        }
    }

    /**
     * Get a department by ID.
     */
    public function getDepartmentById(int $id): ?Department
    {
        return $this->departmentRepository->find($id);
    }

    /**
     * Create a new department.
     *
     * @param  array<string, mixed>  $data
     */
    public function createDepartment(array $data): Department
    {
        return $this->departmentRepository->create($data);
    }

    /**
     * Update a department.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateDepartment(int $id, array $data): ?Department
    {
        $department = $this->departmentRepository->find($id);

        if ($department === null) {
            return null;
        }

        $this->departmentRepository->update($department, $data);

        return $department->fresh();
    }

    /**
     * Delete a department.
     */
    public function deleteDepartment(int $id): bool
    {
        $department = $this->departmentRepository->find($id);

        if ($department === null) {
            return false;
        }

        return $this->departmentRepository->delete($department);
    }
}
