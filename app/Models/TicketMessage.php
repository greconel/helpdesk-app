<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TicketMessage extends Model
{
    protected $fillable = [
        'ticket_id', 'user_id', 'from_email', 'from_name',
        'direction', 'subject', 'body_html', 'body_text',
        'message_id', 'in_reply_to', 'internet_message_id', 'sent_at',
    ];

    protected $casts = ['sent_at' => 'datetime'];

    public function ticket(): BelongsTo { return $this->belongsTo(Ticket::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function attachments(): HasMany { return $this->hasMany(TicketAttachment::class); }
}