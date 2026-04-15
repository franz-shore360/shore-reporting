<?php

namespace App\Console\Commands;

use App\Imports\ImportRegistry;
use App\Models\Import;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ProcessPendingImportsCommand extends Command
{
    protected $signature = 'imports:process-pending';

    protected $description = 'Claim one pending import and process it (entity-specific handler).';

    public function handle(): int
    {
        $import = DB::transaction(function () {
            $row = Import::query()
                ->where('status', Import::STATUS_PENDING)
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($row === null) {
                return null;
            }

            $row->update([
                'status' => Import::STATUS_PROCESSING,
                'started_at' => now(),
            ]);

            return $row;
        });

        if ($import === null) {
            $this->info('No pending imports.');

            return self::SUCCESS;
        }

        $import->refresh();

        try {
            ImportRegistry::make($import)->run();
        } catch (InvalidArgumentException $e) {
            $import->update([
                'status' => Import::STATUS_FAILED,
                'completed_at' => now(),
                'total_items' => 0,
                'total_errors' => 0,
            ]);
            $this->warn('Import '.$import->id.': '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Processed import '.$import->id.' ('.$import->entity_type.').');

        return self::SUCCESS;
    }
}
