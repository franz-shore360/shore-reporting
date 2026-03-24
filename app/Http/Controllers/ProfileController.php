<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function __construct(
        protected UserService $userService
    ) {}

    /**
     * Update the authenticated user's profile.
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (array_key_exists('remove_profile_image', $data)) {
            $data['remove_profile_image'] = filter_var($data['remove_profile_image'], FILTER_VALIDATE_BOOLEAN);
        }

        $user = $this->userService->updateProfile(
            $request->user()->id,
            $data,
            $request->file('profile_image')
        );

        return response()->json($user);
    }
}
