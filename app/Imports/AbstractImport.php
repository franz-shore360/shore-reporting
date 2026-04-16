<?php

namespace App\Imports;

use App\Models\Import;
use App\Notifications\ImportCompletedNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Throwable;

abstract class AbstractImport
{
    protected int $totalItemsProcessed = 0;

    protected int $totalErrorsProcessed = 0;

    /** Stored on the import row: error file basename only (e.g. file_errors.csv). */
    protected ?string $errorFileRelative = null;

    public function __construct(protected Import $import) {}

    /**
     * Import type string as stored on {@see Import::entity_type} (e.g. department).
     */
    abstract public static function entityType(): string;

    /**
     * Process one logical data row (keys are normalized lowercase header names).
     *
     * @param  array<string, string|null>  $row
     */
    abstract protected function processDataRow(array $row, int $dataRowIndex): void;

    /**
     * Run the full import: read file, process rows, persist status/timestamps, notify user.
     */
    final public function run(): void
    {
        $this->import->refresh();
        $this->totalItemsProcessed = 0;
        $this->totalErrorsProcessed = 0;
        $this->errorFileRelative = null;

        try {
            $headerRaw = null;
            /** @var list<array{index: int, raw: list<string|null>}> */
            $dataRows = [];
            /** @var array<int, string> */
            $rowErrors = [];

            foreach ($this->readImportRowPayloads() as $payload) {
                if ($payload['type'] === 'header') {
                    /** @var list<string|null> $raw */
                    $raw = $payload['raw'];
                    $headerRaw = $raw;

                    continue;
                }

                /** @var array{type: 'data', index: int, assoc: array<string, string|null>, raw: list<string|null>} $payload */
                $idx = $payload['index'];
                $assoc = $payload['assoc'];
                $raw = $payload['raw'];

                $dataRows[] = ['index' => $idx, 'raw' => $raw];
                $this->totalItemsProcessed++;

                try {
                    $this->processDataRow($assoc, $idx);
                    $rowErrors[$idx] = '';
                } catch (Throwable $e) {
                    $this->totalErrorsProcessed++;
                    $rowErrors[$idx] = $this->flattenErrorMessage($e->getMessage());
                    Log::warning('Import row skipped', [
                        'import_id' => $this->import->id,
                        'entity_type' => $this->import->entity_type,
                        'row' => $idx,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            if ($this->totalErrorsProcessed > 0 && $headerRaw !== null) {
                try {
                    $this->errorFileRelative = $this->writeErrorResultsFile($headerRaw, $dataRows, $rowErrors);
                } catch (Throwable $e) {
                    Log::error('Failed to write import error file', [
                        'import_id' => $this->import->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            }

            $this->persistTerminalState(Import::STATUS_COMPLETED);
            $this->sendUserNotification();
        } catch (Throwable $e) {
            Log::error('Import failed', [
                'import_id' => $this->import->id,
                'entity_type' => $this->import->entity_type,
                'message' => $e->getMessage(),
            ]);

            $this->persistTerminalState(Import::STATUS_FAILED);
            $this->sendUserNotification();
        }
    }

    protected function flattenErrorMessage(string $message): string
    {
        return trim(str_replace(["\r\n", "\r", "\n"], ' ', $message));
    }

    /**
     * @param  list<string|null>  $headerRaw
     * @param  list<array{index: int, raw: list<string|null>}>  $dataRows
     * @param  array<int, string>  $rowErrors
     */
    protected function writeErrorResultsFile(array $headerRaw, array $dataRows, array $rowErrors): string
    {
        $importPath = $this->absoluteImportPath();
        $extension = strtolower(pathinfo($importPath, PATHINFO_EXTENSION));

        Storage::disk('local')->makeDirectory(rtrim(Import::ERROR_FILE_BASE_PATH, '/'));

        $basename = $this->import->canonicalErrorFileBasename();
        $absolute = Storage::disk('local')->path(Import::ERROR_FILE_BASE_PATH.$basename);

        $headerOut = $this->cellsToScalarStrings($headerRaw);
        $headerOut[] = 'error';

        $rowsOut = [];
        foreach ($dataRows as $entry) {
            $idx = $entry['index'];
            $line = $this->cellsToScalarStrings($entry['raw']);
            $line[] = $rowErrors[$idx] ?? '';

            $rowsOut[] = $line;
        }

        match ($extension) {
            'csv', 'txt' => $this->writeCsvErrorFile($absolute, $headerOut, $rowsOut),
            'xlsx' => $this->writeXlsxErrorFile($absolute, $headerOut, $rowsOut),
            default => throw new \InvalidArgumentException("Cannot write error file for extension: {$extension}"),
        };

        return $basename;
    }

    /**
     * @param  list<string|null>  $cells
     * @return list<string>
     */
    protected function cellsToScalarStrings(array $cells): array
    {
        return array_map(static fn (?string $v): string => $v ?? '', $cells);
    }

    /**
     * @param  list<string>  $headerOut
     * @param  list<list<string>>  $rowsOut
     */
    protected function writeCsvErrorFile(string $absolutePath, array $headerOut, array $rowsOut): void
    {
        $writer = new CsvWriter;
        $writer->openToFile($absolutePath);
        $writer->addRow(Row::fromValues($headerOut));
        foreach ($rowsOut as $line) {
            $writer->addRow(Row::fromValues($line));
        }
        $writer->close();
    }

    /**
     * @param  list<string>  $headerOut
     * @param  list<list<string>>  $rowsOut
     */
    protected function writeXlsxErrorFile(string $absolutePath, array $headerOut, array $rowsOut): void
    {
        $writer = new XlsxWriter;
        $writer->openToFile($absolutePath);
        $writer->getCurrentSheet()->setName('Import');
        $writer->addRow(Row::fromValues($headerOut));
        foreach ($rowsOut as $line) {
            $writer->addRow(Row::fromValues($line));
        }
        $writer->close();
    }

    /**
     * @return \Generator<int, array{type: 'header', raw: list<string|null>}|array{type: 'data', index: int, assoc: array<string, string|null>, raw: list<string|null>}>
     */
    protected function readImportRowPayloads(): \Generator
    {
        $path = $this->absoluteImportPath();
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $reader = match ($extension) {
            'csv', 'txt' => new CsvReader,
            'xlsx' => new XlsxReader,
            default => throw new \InvalidArgumentException(
                "Unsupported file extension for import: .{$extension}. Use csv, txt, or xlsx."
            ),
        };

        $reader->open($path);

        try {
            $headerKeys = null;
            $dataRowIndex = 0;

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    /** @var Row $row */
                    $values = $this->rowToNormalizedStrings($row);

                    if ($headerKeys === null) {
                        $headerKeys = $this->normalizeHeaderKeys($values);
                        yield ['type' => 'header', 'raw' => $values];

                        continue;
                    }

                    if ($this->isRowEmpty($values)) {
                        continue;
                    }

                    $assoc = $this->combineHeaderWithValues($headerKeys, $values);
                    yield ['type' => 'data', 'index' => ++$dataRowIndex, 'assoc' => $assoc, 'raw' => $values];
                }
            }
        } finally {
            $reader->close();
        }
    }

    /**
     * Absolute path to the uploaded file on the local disk.
     */
    protected function absoluteImportPath(): string
    {
        $relative = $this->import->importFileDiskRelative();
        if ($relative === null || $relative === '' || str_contains($relative, '..')) {
            throw new \InvalidArgumentException('Invalid import file path.');
        }

        if (! str_starts_with($relative, Import::IMPORT_FILE_BASE_PATH)) {
            throw new \InvalidArgumentException('Invalid import file path.');
        }

        if (! Storage::disk('local')->exists($relative)) {
            throw new \RuntimeException('Import file is missing from storage.');
        }

        return Storage::disk('local')->path($relative);
    }

    /**
     * @return list<string|null>
     */
    protected function rowToNormalizedStrings(Row $row): array
    {
        $out = [];
        foreach ($row->toArray() as $cell) {
            $out[] = $this->normalizeCellToString($cell);
        }

        return $out;
    }

    protected function normalizeCellToString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return trim((string) $value);
    }

    /**
     * @param  list<string|null>  $values
     * @return list<string>
     */
    protected function normalizeHeaderKeys(array $values): array
    {
        $keys = [];
        foreach ($values as $i => $raw) {
            $key = strtolower(trim((string) ($raw ?? '')));
            if ($i === 0 && $key !== '' && str_starts_with($key, "\xEF\xBB\xBF")) {
                $key = trim(substr($key, 3));
            }
            $keys[] = $key;
        }

        return $keys;
    }

    /**
     * @param  list<string>  $header
     * @param  list<string|null>  $values
     * @return array<string, string|null>
     */
    protected function combineHeaderWithValues(array $header, array $values): array
    {
        $assoc = [];
        foreach ($header as $i => $key) {
            if ($key === '') {
                continue;
            }
            $assoc[$key] = $values[$i] ?? null;
        }

        return $assoc;
    }

    /**
     * @param  list<string|null>  $values
     */
    protected function isRowEmpty(array $values): bool
    {
        foreach ($values as $v) {
            if ($v !== null && $v !== '') {
                return false;
            }
        }

        return true;
    }

    protected function persistTerminalState(string $status): void
    {
        $update = [
            'status' => $status,
            'completed_at' => now(),
            'total_items' => $this->totalItemsProcessed,
            'total_errors' => $this->totalErrorsProcessed,
        ];

        if ($this->errorFileRelative !== null) {
            $update['error_file'] = $this->errorFileRelative;
        }

        $this->import->update($update);
        $this->import->refresh();
    }

    protected function sendUserNotification(): void
    {
        $this->import->loadMissing('user:id,first_name,middle_name,last_name,email');

        $user = $this->import->user;
        if ($user === null || $user->email === null || $user->email === '') {
            $this->import->update(['email_sent' => false]);

            return;
        }

        $user->notify(new ImportCompletedNotification($this->import));
        $this->import->update(['email_sent' => true]);
    }
}
