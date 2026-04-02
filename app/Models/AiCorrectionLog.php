<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiCorrectionLog extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'ai_impact',
        'ai_labels',
        'ai_skill_version',
        'agent_impact',
        'agent_labels',
        'ticket_subject',
        'ticket_description_snippet',
        'correction_type',
        'processed',
    ];

    protected $casts = [
        'ai_labels'    => 'array',
        'agent_labels' => 'array',
        'processed'    => 'boolean',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Was de AI correct voor impact?
     */
    public function impactCorrect(): bool
    {
        return $this->ai_impact === $this->agent_impact;
    }

    /**
     * Was de AI correct voor labels?
     */
    public function labelsCorrect(): bool
    {
        $ai    = collect($this->ai_labels ?? [])->sort()->values()->toArray();
        $agent = collect($this->agent_labels ?? [])->sort()->values()->toArray();

        return $ai === $agent;
    }

    /**
     * Was de AI volledig correct?
     */
    public function fullyCorrect(): bool
    {
        return $this->impactCorrect() && $this->labelsCorrect();
    }
}