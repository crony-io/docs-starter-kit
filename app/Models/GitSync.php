<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GitSync extends Model
{
    protected $fillable = [
        'commit_hash',
        'commit_message',
        'commit_author',
        'commit_date',
        'sync_status',
        'files_changed',
        'sync_details',
        'error_message',
    ];

    protected function casts(): array
    {
        return [
            'commit_date' => 'datetime',
            'files_changed' => 'integer',
            'sync_details' => 'array',
        ];
    }

    public function scopeSuccessful($query)
    {
        return $query->where('sync_status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('sync_status', 'failed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('sync_status', 'in_progress');
    }

    public function scopeRecent($query, int $limit = 20)
    {
        return $query->latest()->limit($limit);
    }

    public function isSuccessful(): bool
    {
        return $this->sync_status === 'success';
    }

    public function isSuccess(): bool
    {
        return $this->isSuccessful();
    }

    public function isFailed(): bool
    {
        return $this->sync_status === 'failed';
    }

    public function isInProgress(): bool
    {
        return $this->sync_status === 'in_progress';
    }

    public function markAsSuccess(int $filesChanged = 0): self
    {
        $this->update([
            'sync_status' => 'success',
            'files_changed' => $filesChanged,
            'error_message' => null,
        ]);

        return $this;
    }

    public function markAsFailed(string $errorMessage): self
    {
        $this->update([
            'sync_status' => 'failed',
            'error_message' => $errorMessage,
        ]);

        return $this;
    }
}
