<?php

namespace App\Events;

use App\Models\AiCorrectionLog;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AiCorrectionLogCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public AiCorrectionLog $log
    ) {}
}