<?php

namespace App\Services;

use App\Contracts\DataTable\DataTableDefinition;
use App\Contracts\DataTable\DataTableQueryable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

/**
 * Centralizes validation and filter mapping for paginated data-table API endpoints.
 *
 * Controllers pass a {@see DataTableQueryable} service (e.g. UserService) and a {@see DataTableDefinition}
 * for that resource.
 */
class DataTableService
{
    /**
     * @return array<string, mixed>
     */
    protected function paginationRules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'sort' => ['sometimes', 'string', 'max:64'],
            'direction' => ['sometimes', 'in:asc,desc'],
        ];
    }

    public function paginate(Request $request, DataTableQueryable $queryable, DataTableDefinition $definition): LengthAwarePaginator
    {
        $validated = $request->validate(array_merge(
            $this->paginationRules(),
            $definition->filterRules(),
        ));

        $perPage = (int) ($validated['per_page'] ?? 25);
        $page = (int) ($validated['page'] ?? 1);
        $sort = (string) ($validated['sort'] ?? $definition->defaultSortColumn());
        $direction = (string) ($validated['direction'] ?? $definition->defaultSortDirection());

        $filters = $this->mapFilters($validated, $definition);

        return $queryable->paginateForDataTable($perPage, $page, $sort, $direction, $filters);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function mapFilters(array $validated, DataTableDefinition $definition): array
    {
        $out = [];
        foreach ($definition->filterKeyMap() as $requestKey => $internalKey) {
            if (! array_key_exists($requestKey, $validated)) {
                continue;
            }
            $value = $validated[$requestKey];
            if ($value !== null && $value !== '') {
                $out[$internalKey] = $value;
            }
        }

        return $out;
    }
}
