<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Ticket extends Model
{
    protected $fillable = ['ticket_number', 'subject', 'description', 'status', 'customer_id', 'assigned_to', 'closed_at'];

    // Een ticket hoort bij één klant
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Een ticket is toegewezen aan één user (agent)
    public function agent(): BelongsTo
    {
        // We moeten 'assigned_to' specificeren omdat de kolomnaam afwijkt van 'user_id'
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // Een ticket kan meerdere labels hebben (Many-to-Many)
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class);
    }
}
