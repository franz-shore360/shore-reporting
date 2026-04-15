<?php

namespace App\DataTable\Definitions;

use App\Contracts\DataTable\DataTableDefinition;

final class ImportDataTableDefinition implements DataTableDefinition
{
    public function filterRules(): array
    {
        return [
            'filter_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter_entity_type' => ['sometimes', 'nullable', 'string', 'max:64'],
            'filter_status' => ['sometimes', 'nullable', 'string', 'max:32'],
            'filter_import_file' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter_email_sent' => ['sometimes', 'nullable', 'string', 'max:16'],
        ];
    }

    public function filterKeyMap(): array
    {
        return [
            'filter_id' => 'id',
            'filter_entity_type' => 'entity_type',
            'filter_status' => 'status',
            'filter_import_file' => 'import_file',
            'filter_email_sent' => 'email_sent',
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
            'entity_type' => 'Entity Type',
            'import_file' => 'Import File',
            'error_file' => 'Error File',
            'status' => 'Status',
            'total_items' => 'Total Items',
            'total_errors' => 'Total Errors',
            'email_sent' => 'Email Sent',
            'user_name' => 'User',
            'created_at' => 'Created At',
            'started_at' => 'Started At',
            'completed_at' => 'Completed At',
        ];
    }
}
