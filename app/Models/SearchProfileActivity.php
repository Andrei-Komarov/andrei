<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class SearchProfileActivity extends Model
{
    /**
     * Get the parsing duration in human-readable format.
     * Returns the duration only for parse completion events (completed/failed/killed).
     */
    public function getParsingDurationAttribute(): ?string
    {
        if (! in_array($this->action, ['parse_completed', 'parse_failed', 'parse_killed'])) {
            return null;
        }

        if (empty($this->run_id)) {
            return null;
        }

        $started = self::where('run_id', $this->run_id)
            ->where('action', 'parse_started')
            ->value('created_at');

        if (! $started) {
            return null;
        }

        $diff = Carbon::parse($started)->diff(Carbon::parse($this->created_at));

        $parts = [];
        if ($diff->h > 0) {
            $parts[] = $diff->h.'ч';
        }
        if ($diff->i > 0) {
            $parts[] = $diff->i.'мин';
        }
        $parts[] = $diff->s.'сек';

        return implode(' ', $parts);
    }

    protected $fillable = [
        'search_profile_id',
        'user_id',
        'profile_owner_id',
        'profile_name',
        'action',
        'description',
        'run_id',
        'pid',
        'metadata',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array'];
    }

    public function searchProfile(): BelongsTo
    {
        return $this->belongsTo(SearchProfile::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
