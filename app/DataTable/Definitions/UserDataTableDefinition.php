<?php

namespace App\DataTable\Definitions;

use App\Contracts\DataTable\DataTableDefinition;

final class UserDataTableDefinition implements DataTableDefinition
{
    public function filterRules(): array
    {
        return [
            'filter_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter_email' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter_full_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter_is_active' => ['sometimes', 'nullable', 'string', 'max:32'],
            'filter_role' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter_department_name' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function filterKeyMap(): array
    {
        return [
            'filter_id' => 'id',
            'filter_email' => 'email',
            'filter_full_name' => 'full_name',
            'filter_is_active' => 'is_active',
            'filter_role' => 'role',
            'filter_department_name' => 'department_name',
        ];
    }

    public function defaultSortColumn(): string
    {
        return 'id';
    }

    public function defaultSortDirection(): string
    {
        return 'desc';
    }
}
