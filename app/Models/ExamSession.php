<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExamSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'job_profile_id',
        'mode',
        'thematic_axis_id',
        'question_ids',
        'total_questions',
        'correct_answers',
        'incorrect_answers',
        'omitted_answers',
        'score',
        'is_passed',
        'time_spent_seconds',
        'time_limit_seconds',
        'completed_at',
    ];

    protected $casts = [
        'question_ids' => 'array',
        'score' => 'decimal:2',
        'is_passed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jobProfile(): BelongsTo
    {
        return $this->belongsTo(JobProfile::class);
    }

    public function thematicAxis(): BelongsTo
    {
        return $this->belongsTo(ThematicAxis::class);
    }

    public function sessionAnswers(): HasMany
    {
        return $this->hasMany(SessionAnswer::class);
    }
}
