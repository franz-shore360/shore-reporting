<?php

namespace App\Repositories\Interfaces;

use App\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface DepartmentRepositoryInterface
{
    /**
     * Get all departments.
     *
     * @return Collection<int, Department>
     */
    public function all(): Collection;

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

    /**
     * Find a department by ID.
     *
     * @param  int  $id
     * @return Department|null
     */
    public function find(int $id): ?Department;

    /**
     * Create a new department.
     *
     * @param  array<string, mixed>  $data
     * @return Department
     */
    public function create(array $data): Department;

    /**
     * Update a department.
     *
     * @param  Department  $department
     * @param  array<string, mixed>  $data
     * @return bool
     */
    public function update(Department $department, array $data): bool;

    /**
     * Delete a department.
     *
     * @param  Department  $department
     * @return bool
     */
    public function delete(Department $department): bool;
}
