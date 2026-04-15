<?php

namespace App\Imports;

use App\Models\Import;

/**
 * Placeholder until GL account CSV rules are defined.
 */
class GlAccountImport extends AbstractImport
{
    public static function entityType(): string
    {
        return Import::ENTITY_GL_ACCOUNT;
    }

    /**
     * {@inheritdoc}
     */
    protected function processDataRow(array $row, int $dataRowIndex): void
    {
        throw new \RuntimeException('GL account import processing is not implemented yet.');
    }
}
