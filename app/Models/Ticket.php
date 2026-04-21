<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use LogsActivity;

    protected $fillable = [
        'ticket_number',
        'subject',
        'description',
        'status',
        'impact',
        'ai_labelled_impact',
        'ai_labelled_labels',
        'customer_id',
        'assigned_to',
        'closed_at',
        'source',
        'last_inbound_message_id',
        'motion_task_id',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'assigned_to', 'impact'])
            ->logOnlyDirty()
            ->setDescriptionForEvent(fn(string $eventName) => "Ticket {$eventName}");
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class)
            ->withPivot('ai_labelled');
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(TicketMessage::class)->orderBy('sent_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(TicketAttachment::class);
    }

    public static function generateTicketNumber(): string
    {
        $last = static::orderBy('id', 'desc')->first();
        $n = $last ? ((int) str_replace('#', '', $last->ticket_number)) + 1 : 1;
        return '#' . str_pad($n, 4, '0', STR_PAD_LEFT);
    }
}