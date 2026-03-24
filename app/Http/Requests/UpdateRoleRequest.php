<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $roleId = $this->route('role');
        $permissionNames = $this->getConfigPermissionNames();

        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($roleId)],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => [Rule::in($permissionNames)],
        ];
    }

    /**
     * @return array<int, string>
     */
    protected function getConfigPermissionNames(): array
    {
        $names = [];
        foreach (config('permissions', []) as $group) {
            foreach (array_keys($group['permissions'] ?? []) as $name) {
                $names[] = $name;
            }
        }
        return $names;
    }
}
