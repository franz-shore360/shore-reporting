<?php

namespace App\Services;

use App\Contracts\DataTable\DataTableQueryable;
use App\Models\User;
use App\Notifications\PasswordChangedNotification;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserService implements DataTableQueryable
{
    protected UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Get all users.
     *
     * @return Collection<int, User>
     */
    public function getAllUsers(): Collection
    {
        return $this->userRepository->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getUsersPaginated(
        int $perPage,
        int $page,
        string $sort,
        string $direction,
        array $filters = [],
    ): LengthAwarePaginator {
        return $this->userRepository->paginate($perPage, $page, $sort, $direction, $filters);
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
        return $this->getUsersPaginated($perPage, $page, $sort, $direction, $filters);
    }

    /**
     * {@inheritdoc}
     */
    public function iterateRowsForDataTableExport(
        string $sort,
        string $direction,
        array $filters = [],
    ): iterable {
        foreach ($this->userRepository->cursorForDataTableExport($sort, $direction, $filters) as $user) {
            yield [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')->join(', '),
                'is_active' => $user->is_active ? 'Active' : 'Inactive',
                'department' => $user->department?->name ?? '',
                'created_at' => $user->created_at?->format('Y-m-d H:i:s') ?? '',
                'updated_at' => $user->updated_at?->format('Y-m-d H:i:s') ?? '',
            ];
        }
    }

    /**
     * Get a user by ID.
     */
    public function getUserById(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    /**
     * Create a new user.
     *
     * @param  array<string, mixed>  $data
     */
    public function createUser(array $data): User
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        return $this->userRepository->create($data);
    }

    /**
     * Update the authenticated user's profile, including optional profile image.
     * Profile image can be an uploaded file or a URL to an image.
     *
     * @param  array<string, mixed>  $data  Validated profile data (profile_image key will be handled separately)
     * @param  UploadedFile|string|null  $profileImage  Optional: uploaded file, image URL, or null
     */
    public function updateProfile(int $id, array $data, UploadedFile|string|null $profileImage = null): ?User
    {
        unset($data['profile_image']);

        if (! empty($data['remove_profile_image'])) {
            $user = $this->userRepository->find($id);
            if ($user !== null && $user->profile_image) {
                $this->deleteProfileImageFile($user->profile_image);
            }
            $data['profile_image'] = null;
            unset($data['remove_profile_image']);
        }

        if ($profileImage !== null) {
            $storedBasename = $profileImage instanceof UploadedFile
                ? $this->storeProfileImageFromUploadedFile($profileImage)
                : $this->storeProfileImageFromUrl($profileImage);

            if ($storedBasename !== null) {
                $user = $this->userRepository->find($id);
                if ($user !== null && $user->profile_image) {
                    $this->deleteProfileImageFile($user->profile_image);
                }
                $data['profile_image'] = $storedBasename;
            }
        }

        return $this->updateUser($id, $data);
    }

    /**
     * Store an uploaded profile image on the public disk (storage/app/public/{User::PROFILE_IMAGE_PATH}).
     * Returns the filename only (for users.profile_image), or null on failure.
     */
    public function storeProfileImageFromUploadedFile(UploadedFile $file): ?string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $basename = Str::random(40).'.'.$ext;
        $stored = $file->storeAs(User::PROFILE_IMAGE_PATH, $basename, 'public');

        return $stored !== false ? basename($stored) : null;
    }

    /**
     * Remove a profile image from disk (users.profile_image is the filename only).
     */
    public function deleteProfileImageFile(?string $profileImageColumnValue): void
    {
        if ($profileImageColumnValue === null || $profileImageColumnValue === '') {
            return;
        }

        Storage::disk('public')->delete(User::PROFILE_IMAGE_PATH.'/'.$profileImageColumnValue);
    }

    /**
     * Download an image from a URL and store it on the public disk (storage/app/public/{User::PROFILE_IMAGE_PATH}).
     * Returns the filename only (for users.profile_image), or null if the download failed.
     */
    protected function storeProfileImageFromUrl(string $url): ?string
    {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $response = Http::timeout(10)->get($url);

        if (! $response->successful()) {
            return null;
        }

        $contentType = $response->header('Content-Type', '');
        $extension = $this->getExtensionFromContentType($contentType) ?? $this->getExtensionFromUrl($url) ?? 'jpg';
        $filename = Str::random(40).'.'.$extension;
        $path = User::PROFILE_IMAGE_PATH.'/'.$filename;

        $stored = Storage::disk('public')->put($path, $response->body());

        return $stored ? $filename : null;
    }

    /**
     * @param  string  $contentType  e.g. "image/jpeg" or "image/png"
     */
    protected function getExtensionFromContentType(string $contentType): ?string
    {
        return match (true) {
            str_contains($contentType, 'jpeg'), str_contains($contentType, 'jpg') => 'jpg',
            str_contains($contentType, 'png') => 'png',
            str_contains($contentType, 'gif') => 'gif',
            str_contains($contentType, 'webp') => 'webp',
            default => null,
        };
    }

    protected function getExtensionFromUrl(string $url): ?string
    {
        $path = parse_url($url, PHP_URL_PATH);
        if ($path === null || $path === '') {
            return null;
        }
        $ext = pathinfo($path, PATHINFO_EXTENSION);

        return in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'gif', 'webp'], true) ? $ext : null;
    }

    /**
     * Update a user.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateUser(int $id, array $data): ?User
    {
        $user = $this->userRepository->find($id);

        if ($user === null) {
            return null;
        }

        $passwordChanging = ! empty($data['password']);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $this->userRepository->update($user, $data);

        $fresh = $user->fresh();

        if ($passwordChanging && $fresh !== null) {
            $this->notifyPasswordChanged($fresh);
        }

        return $fresh;
    }

    /**
     * Send the password-changed email (best-effort; failures are reported, not thrown).
     */
    public function notifyPasswordChanged(User $user): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        try {
            $user->notify(new PasswordChangedNotification);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Delete a user.
     */
    public function deleteUser(int $id): bool
    {
        $user = $this->userRepository->find($id);

        if ($user === null) {
            return false;
        }

        $this->deleteProfileImageFile($user->profile_image);

        return $this->userRepository->delete($user);
    }

    /**
     * Delete multiple users by ID. Skips the authenticated user's ID and missing users.
     *
     * @param  array<int>  $ids
     * @return array{deleted: int, skipped_self: bool, skipped_missing: int}
     */
    public function deleteUsers(array $ids, int $authUserId): array
    {
        $deleted = 0;
        $skippedSelf = false;
        $skippedMissing = 0;

        foreach (array_unique($ids) as $id) {
            $id = (int) $id;
            if ($id === $authUserId) {
                $skippedSelf = true;

                continue;
            }
            if ($this->deleteUser($id)) {
                $deleted++;
            } else {
                $skippedMissing++;
            }
        }

        return [
            'deleted' => $deleted,
            'skipped_self' => $skippedSelf,
            'skipped_missing' => $skippedMissing,
        ];
    }

    /**
     * Update department_id for many users (null clears department).
     *
     * @param  array<int, int>  $userIds
     */
    public function updateDepartmentForUsers(array $userIds, ?int $departmentId): int
    {
        $ids = array_values(array_unique(array_map('intval', $userIds)));
        if ($ids === []) {
            return 0;
        }

        return User::query()->whereIn('id', $ids)->update(['department_id' => $departmentId]);
    }
}
