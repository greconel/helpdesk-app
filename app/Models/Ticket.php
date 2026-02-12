<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ticket extends Model
{
    protected $fillable = [
        'ticket_number', 
        'subject', 
        'description', 
        'status', 
        'impact',      
        'customer_id', 
        'assigned_to', 
        'closed_at'
    ];

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

    // Helper method voor leesbaar impact label
    public function getImpactLabelAttribute(): ?string
    {
        if (!$this->impact) {
            return null; // Of return 'Geen impact'; als je een label wilt tonen
        }

        return match($this->impact) {
            'low' => 'Low impact',
            'medium' => 'Medium impact',
            'high' => 'High impact',
            default => null,
        };
    }

    // Helper method voor impact kleur
    public function getImpactColorAttribute(): string
    {
        if (!$this->impact) {
            return 'bg-gray-100 text-gray-500'; // Grijze badge voor "geen impact"
        }

        return match($this->impact) {
            'low' => 'bg-green-100 text-green-800',
            'medium' => 'bg-yellow-100 text-yellow-800',
            'high' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-500',
        };
    }
}