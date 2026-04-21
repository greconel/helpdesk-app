<?php

namespace App\Http\Controllers;

use App\Models\TicketAttachment;
use Illuminate\Support\Facades\Storage;

class TicketAttachmentController extends Controller
{
    public function download(TicketAttachment $attachment)
    {
        if (!Storage::disk($attachment->disk)->exists($attachment->path)) {
            abort(404, 'Bijlage niet gevonden.');
        }

        return Storage::disk($attachment->disk)->download(
            $attachment->path,
            $attachment->original_filename
        );
    }

    public function show(TicketAttachment $attachment)
    {
        if (!Storage::disk($attachment->disk)->exists($attachment->path)) {
            abort(404, 'Bijlage niet gevonden.');
        }

        $mime = $attachment->mime_type ?? 'application/octet-stream';

        return response(
            Storage::disk($attachment->disk)->get($attachment->path),
            200,
            [
                'Content-Type'        => $mime,
                'Content-Disposition' => 'inline; filename="' . $attachment->original_filename . '"',
            ]
        );
    }
}