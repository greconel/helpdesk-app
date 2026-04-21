<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentProcessor
{
    public function __construct(private GraphMailService $graph) {}

    /**
     * Verwerk inline afbeeldingen in de HTML body.
     * Vervangt cid:-referenties door lokale storage URLs.
     */
    public function processInlineImages(string $bodyHtml, string $graphMessageId): string
    {
        if (! str_contains($bodyHtml, 'cid:')) {
            return $bodyHtml;
        }

        $attachments = $this->graph->getAttachments($graphMessageId);

        foreach ($attachments as $attachment) {
            if (empty($attachment['isInline'])
                || empty($attachment['contentId'])
                || empty($attachment['contentBytes'])
                || ! str_starts_with($attachment['contentType'] ?? '', 'image/')
            ) {
                continue;
            }

            $contentId    = $attachment['contentId'];
            $contentBytes = base64_decode($attachment['contentBytes']);
            $filename     = uniqid('inline_', true) . '_'
                . preg_replace('/[^a-zA-Z0-9_\.-]/', '', $attachment['name'] ?? 'image.png');
            $path = 'attachments/' . $filename;

            Storage::disk('public')->put($path, $contentBytes);

            $bodyHtml = str_replace("cid:{$contentId}", '/storage/' . $path, $bodyHtml);
        }

        return $bodyHtml;
    }

    /**
     * Verwerk niet-inline bijlagen en sla ze op.
     */
    public function processAttachments(
        string $graphMessageId,
        Ticket $ticket,
        TicketMessage $message
    ): void {
        $attachments = $this->graph->getAttachments($graphMessageId);

        foreach ($attachments as $attachment) {
            // Inline afbeeldingen zijn al verwerkt in processInlineImages()
            if (! empty($attachment['isInline'])) {
                continue;
            }

            // Gekoppelde items (bijv. agenda-uitnodigingen) overslaan
            if (($attachment['@odata.type'] ?? '') === '#microsoft.graph.itemAttachment') {
                continue;
            }

            try {
                $bytes = ! empty($attachment['contentBytes'])
                    ? base64_decode($attachment['contentBytes'])
                    : $this->graph->getAttachmentContent($graphMessageId, $attachment['id']);

                if (empty($bytes)) {
                    Log::warning('Bijlage heeft geen inhoud', [
                        'filename' => $attachment['name'] ?? 'onbekend',
                        'ticket'   => $ticket->ticket_number,
                    ]);
                    continue;
                }

                $originalFilename = $attachment['name'] ?? 'bijlage';
                $extension        = pathinfo($originalFilename, PATHINFO_EXTENSION);
                $basename         = pathinfo($originalFilename, PATHINFO_FILENAME);
                $safeFilename     = Str::slug($basename) . ($extension ? '.' . strtolower($extension) : '');
                $path             = 'attachments/' . date('Y/m') . '/' . uniqid() . '_' . $safeFilename;

                Storage::disk('public')->put($path, $bytes);

                TicketAttachment::create([
                    'ticket_id'         => $ticket->id,
                    'ticket_message_id' => $message->id,
                    'filename'          => $safeFilename,
                    'original_filename' => $originalFilename,
                    'mime_type'         => $attachment['contentType'] ?? null,
                    'size'              => strlen($bytes),
                    'disk'              => 'public',
                    'path'              => $path,
                ]);

                Log::info("Bijlage opgeslagen: {$originalFilename} voor ticket {$ticket->ticket_number}");

            } catch (\Throwable $e) {
                Log::error('Bijlage verwerken mislukt', [
                    'filename' => $attachment['name'] ?? 'onbekend',
                    'ticket'   => $ticket->ticket_number,
                    'error'    => $e->getMessage(),
                ]);
            }
        }
    }
}