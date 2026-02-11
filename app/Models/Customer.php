<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = ['name', 'email', 'phone'];

    // Een klant heeft veel tickets
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
