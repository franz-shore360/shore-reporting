<?php

namespace App\DataTable\Definitions;

use App\Contracts\DataTable\DataTableDefinition;

final class DepartmentDataTableDefinition implements DataTableDefinition
{
    public function filterRules(): array
    {
        return [
            'filter_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter_is_active' => ['sometimes', 'nullable', 'string', 'max:32'],
        ];
    }

    public function filterKeyMap(): array
    {
        return [
            'filter_id' => 'id',
            'filter_name' => 'name',
            'filter_is_active' => 'is_active',
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
