<?php

namespace App\Listeners;

use App\Models\EmailLog;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Throwable;

class LogSentMessageToDatabase
{
    private const MAX_BODY_CHARS = 500_000;

    public function handle(MessageSent $event): void
    {
        try {
            $original = $event->sent->getOriginalMessage();
            if (! $original instanceof Email) {
                return;
            }

            $html = $this->truncate($original->getHtmlBody());

            EmailLog::query()->create([
                'sent_at' => now(),
                'from_address' => $this->formatAddresses($original->getFrom()),
                'to_addresses' => $this->formatAddresses($original->getTo()),
                'cc_addresses' => $this->formatAddresses($original->getCc()),
                'bcc_addresses' => $this->formatAddresses($original->getBcc()),
                'subject' => $original->getSubject(),
                'body' => $html !== '' ? $html : null,
            ]);
        } catch (Throwable $e) {
            Log::error('Failed to persist email log row', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<int, Address>  $addresses
     */
    private function formatAddresses(array $addresses): ?string
    {
        if ($addresses === []) {
            return null;
        }

        $parts = [];
        foreach ($addresses as $address) {
            if ($address instanceof Address) {
                $name = $address->getName();
                $email = $address->getAddress();
                $parts[] = $name !== '' ? "{$name} <{$email}>" : $email;
            }
        }

        return $parts === [] ? null : implode(', ', array_unique($parts));
    }

    private function truncate(?string $body): ?string
    {
        if ($body === null || $body === '') {
            return null;
        }

        if (strlen($body) <= self::MAX_BODY_CHARS) {
            return $body;
        }

        return substr($body, 0, self::MAX_BODY_CHARS)."\n\n… [truncated]";
    }
}
