<?php

namespace App\Contracts\DataTable;

/**
 * Describes query validation and filter mapping for a data-table endpoint.
 *
 * Filter rules use request query keys (e.g. filter_email). {@see filterKeyMap()} maps those
 * to internal filter keys passed to {@see DataTableQueryable::paginateForDataTable()}.
 */
interface DataTableDefinition
{
    /**
     * Validation rules for filter query parameters only (not page/per_page/sort/direction).
     *
     * @return array<string, mixed>
     */
    public function filterRules(): array;

    /**
     * Map validated request keys → internal filter keys for the repository/service.
     *
     * @return array<string, string>
     */
    public function filterKeyMap(): array;

    public function defaultSortColumn(): string;

    public function defaultSortDirection(): string;
}
