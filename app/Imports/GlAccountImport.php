<?php

namespace App\Imports;

use App\Models\GlAccount;
use App\Models\Import;

class GlAccountImport extends AbstractImport
{
    public static function entityType(): string
    {
        return Import::ENTITY_GL_ACCOUNT;
    }

    /**
     * {@inheritdoc}
     *
     * Expected columns (header row, case-insensitive): {@code code}, {@code name}.
     * Rows with a {@code code} that already exists update that account’s {@code name}; no duplicate codes are created.
     */
    protected function processDataRow(array $row, int $dataRowIndex): void
    {
        $code = trim((string) ($row['code'] ?? ''));
        if ($code === '') {
            throw new \InvalidArgumentException('Code is required for each row.');
        }

        $name = trim((string) ($row['name'] ?? ''));
        if ($name === '') {
            throw new \InvalidArgumentException('Name is required for each row.');
        }

        if (strlen($code) > 255 || strlen($name) > 255) {
            throw new \InvalidArgumentException('Code and name must be at most 255 characters.');
        }

        GlAccount::query()->updateOrCreate(
            ['code' => $code],
            ['name' => $name],
        );
    }
}
