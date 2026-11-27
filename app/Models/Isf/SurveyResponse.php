<?php

namespace App\Models\Isf;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model SurveyResponse — Jawaban survei member dengan status draft/completed dan pencegahan double-voting.
 */
class SurveyResponse extends Model
{
    use HasFactory;

    protected $table = 'isf_survey_responses';

    protected $fillable = [
        'survey_id',
        'user_id',
        'status',
        'answers',
        'demographics',
        'current_step',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'answers' => 'array',
            'demographics' => 'array',
            'current_step' => 'integer',
            'completed_at' => 'datetime',
        ];
    }

    public function survey(): BelongsTo
    {
        return $this->belongsTo(Survey::class, 'survey_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }
}
