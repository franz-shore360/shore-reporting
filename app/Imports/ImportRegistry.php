<?php

namespace App\Imports;

use App\Models\Import;
use InvalidArgumentException;

final class ImportRegistry
{
    /**
     * @var array<string, class-string<AbstractImport>>
     */
    private const MAP = [
        Import::ENTITY_DEPARTMENT => DepartmentImport::class,
        Import::ENTITY_GL_ACCOUNT => GlAccountImport::class,
    ];

    /**
     * @return class-string<AbstractImport>
     */
    public static function resolveClass(string $entityType): string
    {
        if (! isset(self::MAP[$entityType])) {
            throw new InvalidArgumentException("No import processor registered for entity type: {$entityType}");
        }

        return self::MAP[$entityType];
    }

    public static function make(Import $import): AbstractImport
    {
        $class = self::resolveClass($import->entity_type);

        return new $class($import);
    }
}
