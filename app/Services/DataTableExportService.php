<?php

namespace App\Services;

use App\Contracts\DataTable\DataTableDefinition;
use App\Contracts\DataTable\DataTableQueryable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DataTableExportService
{
    public function __construct(
        protected DataTableService $dataTableService,
    ) {}

    public function stream(
        Request $request,
        DataTableQueryable $queryable,
        DataTableDefinition $definition,
        string $fileStem,
    ): StreamedResponse {
        $validated = $this->dataTableService->validateExportRequest($request, $definition);
        $sort = $validated['sort'];
        $direction = $validated['direction'];
        $filters = $validated['filters'];
        $format = $validated['format'];

        $columns = $definition->exportColumns();
        $extension = $format === 'xlsx' ? 'xlsx' : 'csv';
        $filename = Str::slug($fileStem).'-'.now()->format('Y-m-d-His').'.'.$extension;

        $mime = $format === 'xlsx'
            ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            : 'text/csv; charset=UTF-8';

        return response()->streamDownload(function () use ($queryable, $sort, $direction, $filters, $format, $columns) {
            $rows = $queryable->iterateRowsForDataTableExport($sort, $direction, $filters);
            if ($format === 'csv') {
                $this->writeCsv($rows, $columns);
            } else {
                $this->writeXlsx($rows, $columns);
            }
        }, $filename, [
            'Content-Type' => $mime,
        ]);
    }

    /**
     * @param  iterable<int, array<string, mixed>>  $rows
     * @param  array<string, string>  $columns
     */
    protected function writeCsv(iterable $rows, array $columns): void
    {
        $handle = fopen('php://output', 'w');
        if ($handle === false) {
            return;
        }
        fprintf($handle, "\xEF\xBB\xBF");
        fputcsv($handle, array_values($columns));
        $keys = array_keys($columns);
        foreach ($rows as $row) {
            $line = [];
            foreach ($keys as $key) {
                $line[] = $this->stringifyExportValue($row[$key] ?? null);
            }
            fputcsv($handle, $line);
        }
        fclose($handle);
    }

    /**
     * @param  iterable<int, array<string, mixed>>  $rows
     * @param  array<string, string>  $columns
     */
    protected function writeXlsx(iterable $rows, array $columns): void
    {
        $writer = new Writer;
        $writer->openToFile('php://output');
        $writer->addRow(Row::fromValues(array_values($columns)));
        $keys = array_keys($columns);
        foreach ($rows as $row) {
            $values = [];
            foreach ($keys as $key) {
                $values[] = $this->scalarForXlsx($row[$key] ?? null);
            }
            $writer->addRow(Row::fromValues($values));
        }
        $writer->close();
    }

    protected function stringifyExportValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_scalar($value)) {
            return (string) $value;
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE) ?: '';
    }

    protected function scalarForXlsx(mixed $value): string|int|float|null
    {
        if ($value === null) {
            return null;
        }
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        return $this->stringifyExportValue($value);
    }
}
