<?php

namespace App\Repositories\Interfaces;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    /**
     * Get all users.
     *
     * @return Collection<int, User>
     */
    public function all(): Collection;

    /**
     * Paginate users with optional filters and sort (whitelist enforced in repository).
     *
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
     * Find a user by ID.
     *
     * @param  int  $id
     * @return User|null
     */
    public function find(int $id): ?User;

    /**
     * Find a user by email.
     *
     * @param  string  $email
     * @return User|null
     */
    public function findByEmail(string $email): ?User;

    /**
     * Create a new user.
     *
     * @param  array<string, mixed>  $data
     * @return User
     */
    public function create(array $data): User;

    /**
     * Update a user.
     *
     * @param  User  $user
     * @param  array<string, mixed>  $data
     * @return bool
     */
    public function update(User $user, array $data): bool;

    /**
     * Delete a user.
     *
     * @param  User  $user
     * @return bool
     */
    public function delete(User $user): bool;
}
