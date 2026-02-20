<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Ticket extends Model
{
    use LogsActivity;

    protected $fillable = [
        'ticket_number', 
        'subject', 
        'description', 
        'status', 
        'impact',      
        'customer_id', 
        'assigned_to', 
        'closed_at',
        'email_token'

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
        return $this->belongsToMany(Label::class);
    }

    public function getImpactLabelAttribute(): ?string
    {
        if (!$this->impact) return null;

        return match($this->impact) {
            'low' => 'Low impact',
            'medium' => 'Medium impact',
            'high' => 'High impact',
            default => null,
        };
    }

    public function getImpactColorAttribute(): string
    {
        if (!$this->impact) return 'bg-gray-100 text-gray-500';

        return match($this->impact) {
            'low' => 'bg-green-100 text-green-800',
            'medium' => 'bg-yellow-100 text-yellow-800',
            'high' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-500',
        };
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($ticket) {
            $ticket->email_token = \Illuminate\Support\Str::uuid();
        });
    }

    public function messages()
    {
        return $this->hasMany(TicketMessage::class);
    }
}