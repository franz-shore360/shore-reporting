<?php

namespace App\DataTable\Definitions;

use App\Contracts\DataTable\DataTableDefinition;

final class EmailLogDataTableDefinition implements DataTableDefinition
{
    public function filterRules(): array
    {
        return [
            'filter_id' => ['sometimes', 'nullable', 'string', 'max:255'],
            'filter_subject' => ['sometimes', 'nullable', 'string', 'max:1024'],
            'filter_to_addresses' => ['sometimes', 'nullable', 'string', 'max:1024'],
            'filter_from_address' => ['sometimes', 'nullable', 'string', 'max:1024'],
        ];
    }

    public function filterKeyMap(): array
    {
        return [
            'filter_id' => 'id',
            'filter_subject' => 'subject',
            'filter_to_addresses' => 'to_addresses',
            'filter_from_address' => 'from_address',
        ];
    }

    public function defaultSortColumn(): string
    {
        return 'sent_at';
    }

    public function defaultSortDirection(): string
    {
        return 'desc';
    }

    public function exportColumns(): array
    {
        return [
            'id' => 'ID',
            'subject' => 'Subject',
            'to_addresses' => 'To',
            'from_address' => 'From',
            'sent_at' => 'Sent At',
        ];
    }
}
