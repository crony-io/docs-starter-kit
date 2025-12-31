<?php

namespace App\Events;

use App\Models\GitSync;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GitSyncFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public GitSync $sync,
        public \Exception $exception
    ) {}
}
