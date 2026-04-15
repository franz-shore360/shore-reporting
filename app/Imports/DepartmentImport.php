<?php

namespace App\Imports;

use App\Models\Department;
use App\Models\Import;

class DepartmentImport extends AbstractImport
{
    public static function entityType(): string
    {
        return Import::ENTITY_DEPARTMENT;
    }

    /**
     * {@inheritdoc}
     *
     * Expected columns (header row, case-insensitive): {@code name}, optional {@code is_active}.
     * Active values: 1, true, yes, y (case-insensitive) → active; 0, false, no, n → inactive; blank defaults to active.
     * Rows with a {@code name} that already exists update that department’s {@code is_active}; no duplicate rows are created.
     */
    protected function processDataRow(array $row, int $dataRowIndex): void
    {
        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Name is required for each row.');
        }

        $isActive = $this->parseIsActive($row['is_active'] ?? null);

        Department::query()->updateOrCreate(
            ['name' => $name],
            ['is_active' => $isActive],
        );
    }

    protected function parseIsActive(?string $raw): bool
    {
        if ($raw === null || $raw === '') {
            return true;
        }

        $v = strtolower(trim($raw));

        return ! in_array($v, ['0', 'false', 'no', 'n', 'inactive', 'off'], true);
    }
}
