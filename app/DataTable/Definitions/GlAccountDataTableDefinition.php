<?php

namespace App\DataTable\Definitions;

use App\Contracts\DataTable\DataTableDefinition;

final class GlAccountDataTableDefinition implements DataTableDefinition
{
    public function filterRules(): array
    {
        return [
            'filter_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter_code' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }

    public function filterKeyMap(): array
    {
        return [
            'filter_id' => 'id',
            'filter_name' => 'name',
            'filter_code' => 'code',
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

    public function exportColumns(): array
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'code' => 'Code',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
        ];
    }
}
