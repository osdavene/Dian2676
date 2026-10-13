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
        'options_order',
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
        'options_order' => 'array',
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

    /**
     * Devuelve el mapa de opciones (letras visibles => texto) respetando el orden barajado de la sesión.
     */
    public function getDisplayOptionsFor(Question $question): array
    {
        $origMap = $question->options_map;
        if (empty($this->options_order) || ! isset($this->options_order[$question->id])) {
            return $origMap;
        }

        $order = $this->options_order[$question->id];
        $displayMap = [];
        foreach ($order as $displayKey => $origKey) {
            if (isset($origMap[$origKey])) {
                $displayMap[$displayKey] = $origMap[$origKey];
            }
        }

        return ! empty($displayMap) ? $displayMap : $origMap;
    }

    /**
     * Devuelve la letra visible de la opción correcta para esta sesión.
     */
    public function getCorrectDisplayOptionFor(Question $question): string
    {
        $origCorrect = trim(strtoupper($question->correct_option ?? 'A'));
        if (empty($this->options_order) || ! isset($this->options_order[$question->id])) {
            return $origCorrect;
        }

        $order = $this->options_order[$question->id];
        $displayKey = array_search($origCorrect, $order, true);

        return $displayKey !== false ? (string) $displayKey : $origCorrect;
    }

    /**
     * Convierte una letra visible seleccionada ('A', 'B', 'C'...) a la clave de opción original de la pregunta.
     */
    public function getOriginalOptionFor(Question $question, ?string $selectedDisplayKey): ?string
    {
        if ($selectedDisplayKey === null || $selectedDisplayKey === '') {
            return null;
        }

        $selectedDisplayKey = trim(strtoupper($selectedDisplayKey));

        if (empty($this->options_order) || ! isset($this->options_order[$question->id])) {
            return $selectedDisplayKey;
        }

        return $this->options_order[$question->id][$selectedDisplayKey] ?? $selectedDisplayKey;
    }
}
