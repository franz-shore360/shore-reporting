<?php

namespace App\Services;

use App\Contracts\DataTable\DataTableQueryable;
use App\Models\User;
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
                Storage::disk('public')->delete($user->profile_image);
            }
            $data['profile_image'] = null;
            unset($data['remove_profile_image']);
        }

        if ($profileImage !== null) {
            $storedPath = $profileImage instanceof UploadedFile
                ? $profileImage->store('profile-images', 'public')
                : $this->storeProfileImageFromUrl($profileImage);

            if ($storedPath !== null) {
                $user = $this->userRepository->find($id);
                if ($user !== null && $user->profile_image) {
                    Storage::disk('public')->delete($user->profile_image);
                }
                $data['profile_image'] = $storedPath;
            }
        }

        return $this->updateUser($id, $data);
    }

    /**
     * Download an image from a URL and store it in the profile-images disk.
     * Returns the stored path, or null if the download failed.
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
        $path = 'profile-images/'.$filename;

        $stored = Storage::disk('public')->put($path, $response->body());

        return $stored ? $path : null;
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

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $this->userRepository->update($user, $data);

        return $user->fresh();
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
}
