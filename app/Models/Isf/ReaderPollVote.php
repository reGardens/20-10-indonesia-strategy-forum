<?php

namespace App\Models\Isf;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model Reader Poll Vote — Catatan suara polling per voter hash.
 */
class ReaderPollVote extends Model
{
    protected $table = 'isf_reader_poll_votes';

    protected $fillable = [
        'poll_id',
        'option_id',
        'voter_hash',
        'ip_address',
        'user_id',
    ];

    public function poll(): BelongsTo
    {
        return $this->belongsTo(ReaderPoll::class, 'poll_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

