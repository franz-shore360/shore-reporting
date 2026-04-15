<?php

namespace App\Notifications;

use App\Models\Import;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class ImportFinishedNotification extends Notification
{
    public function __construct(protected Import $import) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->import->refresh();

        $app = config('app.name', 'Shore Reporting');
        $import = $this->import;
        $failed = $import->status === Import::STATUS_FAILED;

        $subjectKey = $failed ? 'import_finished_failed_subject' : 'import_finished_completed_subject';
        $lineKey = $failed ? 'import_finished_failed_line' : 'import_finished_completed_line';

        $entityLabel = match ($import->entity_type) {
            Import::ENTITY_DEPARTMENT => __('notifications.import_entity_department'),
            Import::ENTITY_GL_ACCOUNT => __('notifications.import_entity_gl_account'),
            default => $import->entity_type,
        };

        $mail = (new MailMessage)
            ->subject(__($subjectKey, ['app' => $app]))
            ->greeting(__('notifications.import_finished_greeting', ['name' => $notifiable->full_name]))
            ->line(__($lineKey, [
                'app' => $app,
                'entity' => $entityLabel,
                'status' => $import->status,
            ]))
            ->line(__('notifications.import_finished_counts', [
                'items' => (string) ($import->total_items ?? 0),
                'errors' => (string) ($import->total_errors ?? 0),
            ]));

        $errorDisk = $import->errorFileDiskRelative();
        if (is_string($errorDisk)
            && $errorDisk !== ''
            && str_starts_with($errorDisk, Import::IMPORT_STORAGE_DISK_PREFIX.'/errors/')
            && Storage::disk('local')->exists($errorDisk)) {
            $path = Storage::disk('local')->path($errorDisk);
            $as = basename($errorDisk);
            $mime = str_ends_with(strtolower($errorDisk), '.xlsx')
                ? 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                : 'text/csv; charset=UTF-8';
            $mail->line(__('notifications.import_finished_attachment_line'));
            $mail->attach($path, ['as' => $as, 'mime' => $mime]);
        }

        return $mail->action(__('notifications.import_finished_action'), url('/imports'));
    }
}
