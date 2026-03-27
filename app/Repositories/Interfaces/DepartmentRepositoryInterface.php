<?php

namespace App\Repositories\Interfaces;

use App\Models\Department;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\LazyCollection;

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
     * @param  array<string, mixed>  $filters
     * @return LazyCollection<int, Department>
     */
    public function cursorForDataTableExport(
        string $sort,
        string $direction,
        array $filters = [],
    ): LazyCollection;

    /**
     * Find a department by ID.
     */
    public function find(int $id): ?Department;

    /**
     * Create a new department.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Department;

    /**
     * Update a department.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Department $department, array $data): bool;

    /**
     * Delete a department.
     */
    public function delete(Department $department): bool;
}
