<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    /**
     * @var User
     */
    protected User $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): Collection
    {
        return $this->model->newQuery()->with(['department', 'roles'])->orderBy('id')->get();
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

        $query = $this->model->newQuery()->with(['department', 'roles']);

        $this->applyUserFilters($query, $filters);

        $allowedSorts = [
            'id', 'full_name', 'email', 'is_active', 'department_name',
            'created_at', 'updated_at',
        ];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'id';
        }

        match ($sort) {
            'full_name' => $query
                ->orderBy('first_name', $dir)
                ->orderBy('last_name', $dir)
                ->orderBy('id', 'desc'),
            'department_name' => $query
                ->leftJoin('departments', 'users.department_id', '=', 'departments.id')
                ->select('users.*')
                ->orderBy('departments.name', $dir)
                ->orderBy('users.id', 'desc'),
            'email' => $query->orderBy('email', $dir)->orderBy('id', 'desc'),
            'is_active' => $query->orderBy('is_active', $dir)->orderBy('id', 'desc'),
            'created_at' => $query->orderBy('created_at', $dir)->orderBy('id', 'desc'),
            'updated_at' => $query->orderBy('updated_at', $dir)->orderBy('id', 'desc'),
            default => $query->orderBy('id', $dir),
        };

        return $query->paginate($perPage, ['*'], 'page', $page);
    }

    /**
     * @param  Builder<User>  $query
     * @param  array<string, mixed>  $filters
     */
    protected function applyUserFilters(Builder $query, array $filters): void
    {
        $id = isset($filters['id']) ? trim((string) $filters['id']) : '';
        if ($id !== '') {
            $query->whereRaw('CAST(users.id AS CHAR) LIKE ?', ['%'.$id.'%']);
        }

        $email = isset($filters['email']) ? trim((string) $filters['email']) : '';
        if ($email !== '') {
            $query->where('users.email', 'like', '%'.$email.'%');
        }

        $fullName = isset($filters['full_name']) ? trim((string) $filters['full_name']) : '';
        if ($fullName !== '') {
            $term = '%'.$fullName.'%';
            $query->where(function (Builder $w) use ($term) {
                $w->where('users.first_name', 'like', $term)
                    ->orWhere('users.middle_name', 'like', $term)
                    ->orWhere('users.last_name', 'like', $term);
            });
        }

        $isActive = $filters['is_active'] ?? '';
        if ($isActive === 'Active' || $isActive === '1' || $isActive === 1 || $isActive === true) {
            $query->where('users.is_active', true);
        } elseif ($isActive === 'Inactive' || $isActive === '0' || $isActive === 0 || $isActive === false) {
            $query->where('users.is_active', false);
        }

        $role = isset($filters['role']) ? trim((string) $filters['role']) : '';
        if ($role !== '') {
            $query->whereHas('roles', function (Builder $r) use ($role) {
                $r->where('name', $role);
            });
        }

        $departmentName = isset($filters['department_name']) ? trim((string) $filters['department_name']) : '';
        if ($departmentName !== '') {
            $query->whereHas('department', function (Builder $d) use ($departmentName) {
                $d->where('name', 'like', '%'.$departmentName.'%');
            });
        }
    }

    /**
     * {@inheritdoc}
     */
    public function find(int $id): ?User
    {
        return $this->model->newQuery()->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function findByEmail(string $email): ?User
    {
        return $this->model->newQuery()->where('email', $email)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): User
    {
        return $this->model->newQuery()->create($data);
    }

    /**
     * {@inheritdoc}
     */
    public function update(User $user, array $data): bool
    {
        return $user->update($data);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(User $user): bool
    {
        return $user->delete();
    }
}
