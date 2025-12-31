<?php

namespace App\Events;

use App\Models\GitSync;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GitSyncCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(public GitSync $sync) {}
}
